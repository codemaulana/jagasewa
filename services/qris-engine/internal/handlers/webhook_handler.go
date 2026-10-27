package handlers

import (
	"bytes"
	"encoding/json"
	"log"
	"net/http"
	"time"

	"jagasewa-qris-engine/internal/domain"
	"jagasewa-qris-engine/internal/engine"

	"github.com/gin-gonic/gin"
)

type WebhookHandler struct {
	manager           *engine.Manager
	hub               *engine.Hub
	laravelWebhookURL string
}

func NewWebhookHandler(mgr *engine.Manager, hub *engine.Hub, laravelWebhookURL string) *WebhookHandler {
	return &WebhookHandler{
		manager:           mgr,
		hub:               hub,
		laravelWebhookURL: laravelWebhookURL,
	}
}

// HandleQRISWebhook processes incoming callbacks from bank / payment gateway
func (h *WebhookHandler) HandleQRISWebhook(c *gin.Context) {
	var payload domain.WebhookPayload
	if err := c.ShouldBindJSON(&payload); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid webhook payload: " + err.Error()})
		return
	}

	// 1. Cryptographic Signature Verification
	if !h.manager.VerifySignature(payload.ReferenceCode, payload.TransactionID, payload.Amount, payload.Signature) {
		log.Printf("[SECURITY ALERT] Invalid webhook signature for tx %s (ref %s)", payload.TransactionID, payload.ReferenceCode)
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid signature"})
		return
	}

	// 2. Settle in memory manager
	tx, err := h.manager.SettleTransaction(payload)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 3. Instant Real-time SSE Broadcast (less than 1ms)
	h.hub.Broadcast(domain.StatusEvent{
		ReferenceCode: tx.ReferenceCode,
		Status:        domain.StatusSettled,
		Amount:        tx.Amount,
		Issuer:        payload.Issuer,
		Timestamp:     time.Now(),
	})

	// 4. Asynchronously notify Laravel core monolith
	go h.notifyLaravel(payload)

	c.JSON(http.StatusOK, gin.H{
		"status":  "success",
		"message": "Webhook processed successfully",
	})
}

func (h *WebhookHandler) notifyLaravel(payload domain.WebhookPayload) {
	body, _ := json.Marshal(payload)
	req, err := http.NewRequest("POST", h.laravelWebhookURL, bytes.NewBuffer(body))
	if err != nil {
		log.Printf("[WEBHOOK] Failed to prepare Laravel webhook request: %v", err)
		return
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("User-Agent", "JagaSewa-QRIS-Engine/1.0")

	client := &http.Client{Timeout: 5 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		log.Printf("[WEBHOOK] Laravel callback offline or unreachable (%s): %v", h.laravelWebhookURL, err)
		return
	}
	defer resp.Body.Close()

	log.Printf("[WEBHOOK] Dispatched settlement to Laravel (%s) - HTTP %d", payload.ReferenceCode, resp.StatusCode)
}
