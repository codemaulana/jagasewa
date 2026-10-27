package handlers

import (
	"fmt"
	"io"
	"net/http"
	"time"

	"jagasewa-qris-engine/internal/domain"
	"jagasewa-qris-engine/internal/engine"
	"jagasewa-qris-engine/internal/gateway"

	"github.com/gin-gonic/gin"
)

type QRISHandler struct {
	gateway gateway.PaymentGateway
	manager *engine.Manager
	hub     *engine.Hub
}

func NewQRISHandler(gw gateway.PaymentGateway, mgr *engine.Manager, hub *engine.Hub) *QRISHandler {
	return &QRISHandler{
		gateway: gw,
		manager: mgr,
		hub:     hub,
	}
}

// CreateQRIS generates a dynamic QRIS payload and image
func (h *QRISHandler) CreateQRIS(c *gin.Context) {
	var req domain.QRISRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Payload tidak valid: " + err.Error()})
		return
	}

	res, err := h.gateway.CreateQRIS(c.Request.Context(), req)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	h.manager.StoreTransaction(res)
	c.JSON(http.StatusCreated, res)
}

// GetStatus checks current transaction status via JSON polling
func (h *QRISHandler) GetStatus(c *gin.Context) {
	refCode := c.Param("reference_code")

	tx, err := h.manager.GetTransaction(refCode)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "Transaksi tidak ditemukan"})
		return
	}

	c.JSON(http.StatusOK, tx)
}

// StreamStatus streams real-time status updates via Server-Sent Events (SSE)
func (h *QRISHandler) StreamStatus(c *gin.Context) {
	refCode := c.Param("reference_code")

	// Set SSE Headers
	c.Header("Content-Type", "text/event-stream")
	c.Header("Cache-Control", "no-cache")
	c.Header("Connection", "keep-alive")
	c.Header("Access-Control-Allow-Origin", "*")

	// Subscribe to event hub
	ch := h.hub.Subscribe(refCode)
	defer h.hub.Unsubscribe(refCode, ch)

	// Send initial status immediately
	tx, err := h.manager.GetTransaction(refCode)
	if err == nil {
		c.SSEvent("status", domain.StatusEvent{
			ReferenceCode: tx.ReferenceCode,
			Status:        tx.Status,
			Amount:        tx.Amount,
			Timestamp:     time.Now(),
		})
		c.Writer.Flush()
		if tx.Status == domain.StatusSettled {
			return
		}
	}

	c.Stream(func(w io.Writer) bool {
		select {
		case <-c.Request.Context().Done():
			return false
		case event, ok := <-ch:
			if !ok {
				return false
			}
			c.SSEvent("status", event)
			return event.Status != domain.StatusSettled // Stop stream once settled
		}
	})
}

// SimulatePayment allows testing payment instantly from simulator/browser
func (h *QRISHandler) SimulatePayment(c *gin.Context) {
	var input struct {
		ReferenceCode string `json:"reference_code" binding:"required"`
		Issuer        string `json:"issuer"` // BCA, GoPay, OVO
	}

	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	tx, err := h.manager.GetTransaction(input.ReferenceCode)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "Transaksi tidak ditemukan"})
		return
	}

	issuer := input.Issuer
	if issuer == "" {
		issuer = "BCA QRIS"
	}

	sig := h.manager.GenerateSignature(tx.ReferenceCode, tx.TransactionID, tx.Amount)

	payload := domain.WebhookPayload{
		TransactionID: tx.TransactionID,
		ReferenceCode: tx.ReferenceCode,
		Amount:        tx.Amount,
		Status:        domain.StatusSettled,
		PaymentMethod: "qris",
		Issuer:        issuer,
		SettledAt:     time.Now(),
		Signature:     sig,
	}

	settledTx, err := h.manager.SettleTransaction(payload)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Broadcast to active SSE listeners
	h.hub.Broadcast(domain.StatusEvent{
		ReferenceCode: settledTx.ReferenceCode,
		Status:        domain.StatusSettled,
		Amount:        settledTx.Amount,
		Issuer:        issuer,
		Timestamp:     time.Now(),
	})

	c.JSON(http.StatusOK, gin.H{
		"message":        fmt.Sprintf("Pembayaran %s sebesar Rp %d berhasil disimulasikan!", tx.ReferenceCode, tx.Amount),
		"transaction_id": tx.TransactionID,
		"status":         settledTx.Status,
		"issuer":         issuer,
	})
}
