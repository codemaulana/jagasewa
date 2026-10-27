package main

import (
	"log"
	"net/http"
	"runtime"
	"time"

	"jagasewa-qris-engine/internal/config"
	"jagasewa-qris-engine/internal/engine"
	"jagasewa-qris-engine/internal/gateway"
	"jagasewa-qris-engine/internal/handlers"

	"github.com/gin-gonic/gin"
)

var startTime = time.Now()

func main() {
	cfg := config.Load()

	// Initialize Core Components
	hub := engine.NewHub()
	manager := engine.NewManager(cfg.WebhookSecret)
	simulatorGw := gateway.NewSimulatorGateway()

	// Initialize Handlers
	qrisHandler := handlers.NewQRISHandler(simulatorGw, manager, hub)
	webhookHandler := handlers.NewWebhookHandler(manager, hub, cfg.LaravelWebhookURL)
	simulatorHandler := handlers.NewSimulatorHandler()

	router := gin.Default()

	// CORS Middleware for Mobile Browsers & Laravel
	router.Use(func(c *gin.Context) {
		c.Writer.Header().Set("Access-Control-Allow-Origin", "*")
		c.Writer.Header().Set("Access-Control-Allow-Methods", "POST, GET, OPTIONS")
		c.Writer.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With")
		if c.Request.Method == "OPTIONS" {
			c.AbortWithStatus(http.StatusNoContent)
			return
		}
		c.Next()
	})

	// Healthcheck & Metrics
	router.GET("/health", func(c *gin.Context) {
		var memStats runtime.MemStats
		runtime.ReadMemStats(&memStats)

		c.JSON(http.StatusOK, gin.H{
			"status":            "healthy",
			"service":           "jagasewa-qris-engine",
			"uptime_seconds":    int(time.Since(startTime).Seconds()),
			"goroutines":        runtime.NumGoroutine(),
			"memory_alloc_mb":   float64(memStats.Alloc) / 1024 / 1024,
			"gateway_provider":  cfg.GatewayProvider,
			"laravel_callback":  cfg.LaravelWebhookURL,
		})
	})

	// Interactive Testing Simulator
	router.GET("/simulator", simulatorHandler.ShowSimulator)

	// API v1 Routes
	v1 := router.Group("/api/v1")
	{
		// QRIS Management
		v1.POST("/qris/create", qrisHandler.CreateQRIS)
		v1.GET("/qris/status/:reference_code", qrisHandler.GetStatus)
		v1.GET("/qris/stream/:reference_code", qrisHandler.StreamStatus)
		v1.POST("/qris/simulate-pay", qrisHandler.SimulatePayment)

		// Webhook Receiver
		v1.POST("/webhooks/qris", webhookHandler.HandleQRISWebhook)
	}

	log.Printf("[JagaSewa QRIS Engine] Microservice running on :%s", cfg.Port)
	if err := router.Run(":" + cfg.Port); err != nil {
		log.Fatalf("Server failed to start: %v", err)
	}
}
