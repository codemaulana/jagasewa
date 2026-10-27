package config

import (
	"os"

	"github.com/joho/godotenv"
)

type Config struct {
	Port              string
	SecretKey         string
	WebhookSecret     string
	LaravelWebhookURL string
	GatewayProvider   string // "simulator", "tripay", "midtrans"
}

func Load() *Config {
	_ = godotenv.Load()

	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}

	secretKey := os.Getenv("QRIS_SECRET_KEY")
	if secretKey == "" {
		secretKey = "jagasewa-qris-secret-key-default-2026"
	}

	webhookSecret := os.Getenv("QRIS_WEBHOOK_SECRET")
	if webhookSecret == "" {
		webhookSecret = "jagasewa-webhook-signature-key-2026"
	}

	laravelWebhookURL := os.Getenv("LARAVEL_WEBHOOK_URL")
	if laravelWebhookURL == "" {
		laravelWebhookURL = "http://127.0.0.1:8000/api/webhooks/qris-settled"
	}

	gatewayProvider := os.Getenv("GATEWAY_PROVIDER")
	if gatewayProvider == "" {
		gatewayProvider = "simulator"
	}

	return &Config{
		Port:              port,
		SecretKey:         secretKey,
		WebhookSecret:     webhookSecret,
		LaravelWebhookURL: laravelWebhookURL,
		GatewayProvider:   gatewayProvider,
	}
}
