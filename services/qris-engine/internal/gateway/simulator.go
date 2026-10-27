package gateway

import (
	"context"
	"encoding/base64"
	"fmt"
	"time"

	"jagasewa-qris-engine/internal/domain"

	"github.com/skip2/go-qrcode"
)

type SimulatorGateway struct{}

func NewSimulatorGateway() *SimulatorGateway {
	return &SimulatorGateway{}
}

func (s *SimulatorGateway) CreateQRIS(ctx context.Context, req domain.QRISRequest) (*domain.QRISResponse, error) {
	now := time.Now()
	expiryMinutes := req.ExpiryMinutes
	if expiryMinutes <= 0 {
		expiryMinutes = 15
	}
	expiresAt := now.Add(time.Duration(expiryMinutes) * time.Minute)

	txID := fmt.Sprintf("TX-QRIS-%s-%d", req.ReferenceCode, now.Unix())

	// Standard simulated ASPI QRIS payload format
	qrString := fmt.Sprintf("00020101021226580014ID.LINKAJA.WWW01189360091100210000000215%d0303UME520454995303360540%d5802ID5910JAGASEWA6007JAKARTA62070703A016304", req.VendorID, req.Amount)

	// Generate QR Code PNG
	pngBytes, err := qrcode.Encode(qrString, qrcode.Medium, 256)
	if err != nil {
		return nil, fmt.Errorf("failed to generate QR code image: %w", err)
	}

	qrBase64 := "data:image/png;base64," + base64.StdEncoding.EncodeToString(pngBytes)

	return &domain.QRISResponse{
		TransactionID: txID,
		ReferenceCode: req.ReferenceCode,
		Amount:        req.Amount,
		QRString:      qrString,
		QRImageBase64: qrBase64,
		Status:        domain.StatusPending,
		ExpiresAt:     expiresAt,
		CreatedAt:     now,
	}, nil
}
