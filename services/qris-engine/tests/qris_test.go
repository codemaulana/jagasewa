package tests

import (
	"context"
	"testing"
	"time"

	"jagasewa-qris-engine/internal/domain"
	"jagasewa-qris-engine/internal/engine"
	"jagasewa-qris-engine/internal/gateway"
)

func TestCreateQRISSimulator(t *testing.T) {
	gw := gateway.NewSimulatorGateway()

	req := domain.QRISRequest{
		BookingULID:   "01HBZZ1234567890ABCDEFGHJK",
		ReferenceCode: "JS-202610-TEST1",
		Amount:        150000,
		VendorID:      1,
		CustomerName:  "Budi Santoso",
		ExpiryMinutes: 15,
	}

	res, err := gw.CreateQRIS(context.Background(), req)
	if err != nil {
		t.Fatalf("Failed to create QRIS: %v", err)
	}

	if res.ReferenceCode != "JS-202610-TEST1" {
		t.Errorf("Expected reference code JS-202610-TEST1, got %s", res.ReferenceCode)
	}

	if res.Amount != 150000 {
		t.Errorf("Expected amount 150000, got %d", res.Amount)
	}

	if res.Status != domain.StatusPending {
		t.Errorf("Expected status pending, got %s", res.Status)
	}

	if res.QRImageBase64 == "" {
		t.Errorf("Expected QRImageBase64 to be generated")
	}
}

func TestSignatureVerificationAndSettlement(t *testing.T) {
	secret := "test-secret-key-12345"
	mgr := engine.NewManager(secret)

	// 1. Store initial pending transaction
	tx := &domain.QRISResponse{
		TransactionID: "TX-TEST-001",
		ReferenceCode: "JS-REF-001",
		Amount:        75000,
		Status:        domain.StatusPending,
		CreatedAt:     time.Now(),
	}
	mgr.StoreTransaction(tx)

	// 2. Test valid signature
	validSig := mgr.GenerateSignature(tx.ReferenceCode, tx.TransactionID, tx.Amount)
	if !mgr.VerifySignature(tx.ReferenceCode, tx.TransactionID, tx.Amount, validSig) {
		t.Errorf("Signature verification failed for valid signature")
	}

	// 3. Test invalid signature
	if mgr.VerifySignature(tx.ReferenceCode, tx.TransactionID, tx.Amount, "invalid-sig") {
		t.Errorf("Signature verification should have failed for fake signature")
	}

	// 4. Test settlement
	payload := domain.WebhookPayload{
		TransactionID: tx.TransactionID,
		ReferenceCode: tx.ReferenceCode,
		Amount:        tx.Amount,
		Status:        domain.StatusSettled,
		Issuer:        "BCA QRIS",
		Signature:     validSig,
	}

	settledTx, err := mgr.SettleTransaction(payload)
	if err != nil {
		t.Fatalf("SettleTransaction failed: %v", err)
	}

	if settledTx.Status != domain.StatusSettled {
		t.Errorf("Expected status settled, got %s", settledTx.Status)
	}

	// 5. Test idempotency (replaying same payload should succeed without error)
	replayedTx, err := mgr.SettleTransaction(payload)
	if err != nil {
		t.Fatalf("Replay idempotency failed: %v", err)
	}
	if replayedTx.Status != domain.StatusSettled {
		t.Errorf("Expected settled status on replay")
	}
}

func TestSSEHubBroadcast(t *testing.T) {
	hub := engine.NewHub()
	refCode := "JS-SSE-TEST"

	ch := hub.Subscribe(refCode)

	event := domain.StatusEvent{
		ReferenceCode: refCode,
		Status:        domain.StatusSettled,
		Amount:        200000,
		Issuer:        "GoPay",
		Timestamp:     time.Now(),
	}

	go hub.Broadcast(event)

	select {
	case received := <-ch:
		if received.ReferenceCode != refCode || received.Status != domain.StatusSettled {
			t.Errorf("Received mismatched event: %+v", received)
		}
	case <-time.After(1 * time.Second):
		t.Fatalf("Timeout waiting for broadcast event")
	}

	hub.Unsubscribe(refCode, ch)
}
