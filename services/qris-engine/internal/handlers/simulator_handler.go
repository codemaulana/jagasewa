package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

type SimulatorHandler struct{}

func NewSimulatorHandler() *SimulatorHandler {
	return &SimulatorHandler{}
}

func (h *SimulatorHandler) ShowSimulator(c *gin.Context) {
	html := `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JagaSewa - QRIS Engine Simulator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#F6F3EC] text-[#1B1B19] min-h-screen p-4 sm:p-8 flex items-center justify-center">
    <div class="max-w-md w-full bg-white border border-[#E7E5E0] rounded-2xl shadow-sm p-6 space-y-5">
        <div class="flex items-center justify-between pb-4 border-b border-[#E7E5E0]">
            <div>
                <h1 class="text-lg font-bold">QRIS Engine Simulator</h1>
                <p class="text-xs text-gray-500">Uji coba pembuatan QRIS & real-time webhook</p>
            </div>
            <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-[#C6F432] text-[#1B1B19]">Go Engine</span>
        </div>

        <!-- Form Pembuatan QRIS -->
        <div id="step-create" class="space-y-3">
            <div>
                <label class="block text-xs font-bold mb-1">Kode Referensi Booking</label>
                <input id="refCode" type="text" value="JS-202610-DEMO1" class="w-full text-xs px-3 py-2 border rounded-lg bg-[#F6F3EC]">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1">Nominal Sewa (Rp)</label>
                <input id="amount" type="number" value="150000" class="w-full text-xs px-3 py-2 border rounded-lg bg-[#F6F3EC]">
            </div>
            <button onclick="createQRIS()" class="w-full py-2.5 bg-[#1B1B19] text-white font-bold text-xs rounded-lg hover:opacity-90 transition">
                Generate QRIS Dinamis
            </button>
        </div>

        <!-- Tampilan QRIS & Real-time Live Status -->
        <div id="step-qr" class="hidden space-y-4 text-center">
            <div class="p-4 border border-[#E7E5E0] rounded-xl bg-[#F6F3EC] inline-block mx-auto">
                <img id="qrImage" src="" alt="QRIS" class="w-56 h-56 mx-auto rounded-lg">
            </div>

            <div class="space-y-1">
                <div class="text-xs text-gray-500">Total Pembayaran:</div>
                <div id="displayAmount" class="text-2xl font-black text-[#1B1B19]"></div>
                <div id="displayRef" class="text-xs font-mono font-bold text-gray-600"></div>
            </div>

            <!-- Status Badge Live -->
            <div id="statusBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Menunggu Pembayaran (Scan QR)...</span>
            </div>

            <!-- Tombol Simulasi Bayar -->
            <div class="pt-2 flex gap-2">
                <button onclick="simulatePay('BCA QRIS')" class="flex-1 py-2 bg-blue-600 text-white font-semibold text-xs rounded-lg hover:bg-blue-700 transition">
                    Simulasikan Bayar (BCA)
                </button>
                <button onclick="simulatePay('GoPay')" class="flex-1 py-2 bg-emerald-600 text-white font-semibold text-xs rounded-lg hover:bg-emerald-700 transition">
                    Simulasikan Bayar (GoPay)
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentRef = '';
        let eventSource = null;

        async function createQRIS() {
            const refCode = document.getElementById('refCode').value;
            const amount = parseInt(document.getElementById('amount').value);

            const res = await fetch('/api/v1/qris/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_ulid: '01HBZZ00000000000000000000',
                    reference_code: refCode,
                    amount: amount,
                    vendor_id: 1,
                    customer_name: 'Budi Santoso',
                    customer_phone: '081234567890'
                })
            });

            const data = await res.json();
            currentRef = data.reference_code;

            document.getElementById('qrImage').src = data.qr_image_base64;
            document.getElementById('displayAmount').innerText = 'Rp ' + amount.toLocaleString('id-ID');
            document.getElementById('displayRef').innerText = currentRef;
            document.getElementById('step-create').classList.add('hidden');
            document.getElementById('step-qr').classList.remove('hidden');

            // Connect Real-Time SSE Stream!
            listenSSE(currentRef);
        }

        function listenSSE(ref) {
            if (eventSource) eventSource.close();
            eventSource = new EventSource('/api/v1/qris/stream/' + ref);

            eventSource.addEventListener('status', (e) => {
                const data = JSON.parse(e.data);
                if (data.status === 'settled') {
                    const badge = document.getElementById('statusBadge');
                    badge.className = 'inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold bg-green-100 text-green-800';
                    badge.innerHTML = '✓ Pembayaran Berhasil Lunas via ' + (data.issuer || 'QRIS') + '!';
                    eventSource.close();
                }
            });
        }

        async function simulatePay(issuer) {
            await fetch('/api/v1/qris/simulate-pay', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reference_code: currentRef,
                    issuer: issuer
                })
            });
        }
    </script>
</body>
</html>`
	c.Header("Content-Type", "text/html; charset=utf-8")
	c.String(http.StatusOK, html)
}
