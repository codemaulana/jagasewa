package domain

import "time"

// TransactionStatus enum
type TransactionStatus string

const (
	StatusPending   TransactionStatus = "pending"
	StatusSettled   TransactionStatus = "settled"
	StatusExpired   TransactionStatus = "expired"
	StatusFailed    TransactionStatus = "failed"
)

// QRISRequest DTO from client / Laravel
type QRISRequest struct {
	BookingULID   string `json:"booking_ulid" binding:"required"`
	ReferenceCode string `json:"reference_code" binding:"required"`
	Amount        uint64 `json:"amount" binding:"required,gt=0"`
	VendorID      uint64 `json:"vendor_id" binding:"required"`
	CustomerName  string `json:"customer_name" binding:"required"`
	CustomerPhone string `json:"customer_phone"`
	ExpiryMinutes int    `json:"expiry_minutes"` // default 15 mins
}

// QRISResponse DTO returned to client
type QRISResponse struct {
	TransactionID string            `json:"transaction_id"`
	ReferenceCode string            `json:"reference_code"`
	Amount        uint64            `json:"amount"`
	QRString      string            `json:"qr_string"`
	QRImageBase64 string            `json:"qr_image_base64"`
	Status        TransactionStatus `json:"status"`
	ExpiresAt     time.Time         `json:"expires_at"`
	CreatedAt     time.Time         `json:"created_at"`
}

// WebhookPayload represents an incoming payment notification from gateway/bank
type WebhookPayload struct {
	TransactionID string            `json:"transaction_id" binding:"required"`
	ReferenceCode string            `json:"reference_code" binding:"required"`
	Amount        uint64            `json:"amount" binding:"required"`
	Status        TransactionStatus `json:"status" binding:"required"`
	PaymentMethod string            `json:"payment_method"`
	Issuer        string            `json:"issuer"` // e.g. BCA, GoPay, OVO, ShopeePay
	SettledAt     time.Time         `json:"settled_at"`
	Signature     string            `json:"signature" binding:"required"`
}

// StatusEvent represents an SSE event pushed to active client browsers
type StatusEvent struct {
	ReferenceCode string            `json:"reference_code"`
	Status        TransactionStatus `json:"status"`
	Amount        uint64            `json:"amount"`
	Issuer        string            `json:"issuer"`
	Timestamp     time.Time         `json:"timestamp"`
}
