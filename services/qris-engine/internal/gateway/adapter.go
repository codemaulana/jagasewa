package gateway

import (
	"context"
	"jagasewa-qris-engine/internal/domain"
)

// PaymentGateway interface allows swapping between Simulator, Tripay, and Midtrans
type PaymentGateway interface {
	CreateQRIS(ctx context.Context, req domain.QRISRequest) (*domain.QRISResponse, error)
}
