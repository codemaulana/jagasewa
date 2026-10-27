package engine

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"errors"
	"sync"

	"jagasewa-qris-engine/internal/domain"
)

var (
	ErrTransactionNotFound = errors.New("transaksi QRIS tidak ditemukan")
	ErrAlreadySettled      = errors.New("transaksi sudah berstatus lunas (settled)")
	ErrAmountMismatch      = errors.New("nominal pembayaran tidak sesuai dengan tagihan")
	ErrInvalidSignature    = errors.New("signature webhook tidak valid")
)

type Manager struct {
	mu            sync.RWMutex
	transactions  map[string]*domain.QRISResponse // keyed by reference_code
	processedKeys map[string]bool                 // idempotency cache
	webhookSecret string
}

func NewManager(webhookSecret string) *Manager {
	return &Manager{
		transactions:  make(map[string]*domain.QRISResponse),
		processedKeys: make(map[string]bool),
		webhookSecret: webhookSecret,
	}
}

// StoreTransaction saves a newly generated QRIS transaction
func (m *Manager) StoreTransaction(res *domain.QRISResponse) {
	m.mu.Lock()
	defer m.mu.Unlock()
	m.transactions[res.ReferenceCode] = res
}

// GetTransaction retrieves a transaction by reference code
func (m *Manager) GetTransaction(referenceCode string) (*domain.QRISResponse, error) {
	m.mu.RLock()
	defer m.mu.RUnlock()

	tx, exists := m.transactions[referenceCode]
	if !exists {
		return nil, ErrTransactionNotFound
	}
	return tx, nil
}

// VerifySignature validates HMAC-SHA256 signature for incoming webhooks
func (m *Manager) VerifySignature(refCode, txID string, amount uint64, incomingSig string) bool {
	message := []byte(refCode + ":" + txID + ":" + string(rune(amount)))
	mac := hmac.New(sha256.New, []byte(m.webhookSecret))
	mac.Write(message)
	expectedSig := hex.EncodeToString(mac.Sum(nil))

	return hmac.Equal([]byte(expectedSig), []byte(incomingSig))
}

// GenerateSignature generates expected signature (used by simulator & tests)
func (m *Manager) GenerateSignature(refCode, txID string, amount uint64) string {
	message := []byte(refCode + ":" + txID + ":" + string(rune(amount)))
	mac := hmac.New(sha256.New, []byte(m.webhookSecret))
	mac.Write(message)
	return hex.EncodeToString(mac.Sum(nil))
}

// SettleTransaction updates transaction status to settled if valid
func (m *Manager) SettleTransaction(payload domain.WebhookPayload) (*domain.QRISResponse, error) {
	m.mu.Lock()
	defer m.mu.Unlock()

	// 1. Idempotency check
	idempotencyKey := payload.TransactionID + ":" + string(payload.Status)
	if m.processedKeys[idempotencyKey] {
		// Already processed cleanly
		return m.transactions[payload.ReferenceCode], nil
	}

	// 2. Fetch existing transaction
	tx, exists := m.transactions[payload.ReferenceCode]
	if !exists {
		return nil, ErrTransactionNotFound
	}

	if tx.Status == domain.StatusSettled {
		return tx, nil
	}

	// 3. Amount verification
	if tx.Amount != payload.Amount {
		return nil, ErrAmountMismatch
	}

	// 4. Update status
	tx.Status = domain.StatusSettled
	m.processedKeys[idempotencyKey] = true

	return tx, nil
}
