package engine

import (
	"sync"

	"jagasewa-qris-engine/internal/domain"
)

// Hub manages active SSE client connections per reference code
type Hub struct {
	mu          sync.RWMutex
	subscribers map[string][]chan domain.StatusEvent
}

func NewHub() *Hub {
	return &Hub{
		subscribers: make(map[string][]chan domain.StatusEvent),
	}
}

// Subscribe registers a new listener for a given reference_code
func (h *Hub) Subscribe(referenceCode string) chan domain.StatusEvent {
	h.mu.Lock()
	defer h.mu.Unlock()

	ch := make(chan domain.StatusEvent, 5)
	h.subscribers[referenceCode] = append(h.subscribers[referenceCode], ch)
	return ch
}

// Unsubscribe removes a listener when connection closes
func (h *Hub) Unsubscribe(referenceCode string, ch chan domain.StatusEvent) {
	h.mu.Lock()
	defer h.mu.Unlock()

	listeners := h.subscribers[referenceCode]
	for i, listener := range listeners {
		if listener == ch {
			h.subscribers[referenceCode] = append(listeners[:i], listeners[i+1:]...)
			close(ch)
			break
		}
	}
	if len(h.subscribers[referenceCode]) == 0 {
		delete(h.subscribers, referenceCode)
	}
}

// Broadcast sends a status event to all listeners of reference_code
func (h *Hub) Broadcast(event domain.StatusEvent) {
	h.mu.RLock()
	defer h.mu.RUnlock()

	listeners := h.subscribers[event.ReferenceCode]
	for _, ch := range listeners {
		select {
		case ch <- event:
		default:
			// Non-blocking drop if buffer full
		}
	}
}
