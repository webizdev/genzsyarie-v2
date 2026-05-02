<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Peserta - Syirkah Bisnis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap');
        body { 
            font-family: 'Outfit', sans-serif;
            transition: background-color 0.6s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .state-success { background-color: #059669; }
        .state-error { background-color: #dc2626; }
        .state-waiting { background-color: #ffffff; }
        
        .card-blur {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="state-waiting flex items-center justify-center min-h-screen p-6">
    <div id="content" class="text-center max-w-md w-full transition-all duration-500 transform">
        <!-- Default State: Form -->
        <div id="form-container" class="space-y-8 py-12">
            <div class="mb-10">
                <div class="w-20 h-20 bg-gray-900 text-white rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl rotate-3">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <h1 class="text-4xl font-black text-gray-900 mb-3 tracking-tight">E-Checkin</h1>
                <p class="text-gray-500 font-medium">Syirkah Bisnis Mastery v2.0</p>
            </div>

            <div class="space-y-4">
                <div class="relative">
                    <input type="tel" id="wa-input" placeholder="Masukkan No. WhatsApp" 
                        class="w-full px-6 py-5 border-2 border-gray-100 rounded-3xl text-xl font-bold focus:outline-none focus:border-gray-900 transition-all shadow-sm placeholder:text-gray-300">
                </div>
                <button onclick="handleCheckin()" id="submit-btn" 
                    class="w-full bg-gray-900 text-white font-black py-5 rounded-3xl text-lg hover:bg-black hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl shadow-gray-200">
                    Submit Kehadiran
                </button>
            </div>
            
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest pt-8">Syirkah Bisnis Mastery</p>
        </div>

        <!-- List State (Hidden by default) -->
        <div id="list-container" class="hidden space-y-6 py-12 animate-in fade-in zoom-in duration-500">
            <h2 class="text-3xl font-black text-gray-900 mb-2">Pilih Peserta</h2>
            <p class="text-gray-500 font-medium mb-8">Ditemukan beberapa peserta dengan nomor ini</p>
            <div id="participant-list" class="space-y-4">
                <!-- List will be injected here -->
            </div>
            <button onclick="resetUI()" class="mt-8 text-gray-500 font-bold hover:text-gray-900 transition-colors">
                Batal
            </button>
        </div>

        <!-- Result State (Hidden by default) -->
        <div id="result-container" class="hidden py-12 animate-in fade-in zoom-in duration-500">
             <div id="result-icon-bg" class="mb-10 mx-auto w-32 h-32 rounded-full flex items-center justify-center text-white text-6xl shadow-2xl card-blur">
                 <span id="result-icon"></span>
             </div>
             <h2 id="result-title" class="text-5xl font-black text-white mb-4 tracking-tighter uppercase"></h2>
             <p id="result-subtitle" class="text-white text-2xl font-bold mb-12"></p>
             
             <button onclick="resetUI()" class="px-10 py-4 bg-white text-gray-900 font-black rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-xl">
                 KEMBALI
             </button>
        </div>
    </div>

    <script>
        // Check URL for automatic check-in (e.g. checkin.php?wa=08123)
        const urlParams = new URLSearchParams(window.location.search);
        const waParam = urlParams.get('wa');
        if (waParam) {
            document.getElementById('wa-input').value = waParam;
            // Short delay to let user see the white screen first
            setTimeout(() => handleCheckin(), 800);
        }

        function handleCheckin() {
            const wa = document.getElementById('wa-input').value.trim();
            if (!wa) return;

            const btn = document.getElementById('submit-btn');
            
            btn.disabled = true;
            btn.textContent = 'MENCARI...';
            btn.classList.add('opacity-50');

            fetch('api.php?action=search_wa', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ whatsapp: wa })
            })
            .then(res => res.json().then(data => ({ status: res.status, data })))
            .then(({ status, data }) => {
                if (status === 200 && data.success && data.data) {
                    showParticipantList(data.data);
                } else {
                    showError(data.message || 'Nomor tidak terdaftar atau belum dikonfirmasi');
                }
            })
            .catch(err => showError('Gangguan koneksi server'))
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'SUBMIT KEHADIRAN';
                btn.classList.remove('opacity-50');
            });
        }

        function showParticipantList(participants) {
            document.body.className = 'state-waiting flex items-center justify-center min-h-screen p-6';
            document.getElementById('form-container').classList.add('hidden');
            document.getElementById('result-container').classList.add('hidden');
            document.getElementById('list-container').classList.remove('hidden');

            const listDiv = document.getElementById('participant-list');
            listDiv.innerHTML = '';

            participants.forEach(p => {
                const card = document.createElement('div');
                card.className = 'bg-white p-5 rounded-3xl border-2 border-gray-100 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4 transition-all hover:border-gray-300';
                
                const nameDiv = document.createElement('div');
                nameDiv.className = 'font-bold text-gray-900 text-lg text-center sm:text-left';
                nameDiv.textContent = p.full_name;

                const btn = document.createElement('button');
                if (p.checked_in_at !== null) {
                    btn.className = 'w-full sm:w-auto px-6 py-3 rounded-2xl font-bold bg-gray-100 text-gray-400 cursor-not-allowed';
                    btn.textContent = 'Sudah Hadir';
                    btn.disabled = true;
                } else {
                    btn.className = 'w-full sm:w-auto px-6 py-3 rounded-2xl font-bold bg-gray-900 text-white hover:bg-black transition-colors';
                    btn.textContent = 'Check In';
                    btn.onclick = () => confirmCheckin(p.id);
                }

                card.appendChild(nameDiv);
                card.appendChild(btn);
                listDiv.appendChild(card);
            });
        }

        function confirmCheckin(id) {
            document.getElementById('list-container').classList.add('opacity-50', 'pointer-events-none');

            fetch('api.php?action=checkin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json().then(data => ({ status: res.status, data })))
            .then(({ status, data }) => {
                document.getElementById('list-container').classList.remove('opacity-50', 'pointer-events-none');
                if (status === 200 && data.success) {
                    showSuccess(data);
                } else {
                    showError(data.message || 'Gagal check-in');
                }
            })
            .catch(err => {
                document.getElementById('list-container').classList.remove('opacity-50', 'pointer-events-none');
                showError('Gangguan koneksi server');
            });
        }

        function showSuccess(data) {
            document.body.className = 'state-success flex items-center justify-center min-h-screen p-6';
            document.getElementById('form-container').classList.add('hidden');
            if (document.getElementById('list-container')) {
                document.getElementById('list-container').classList.add('hidden');
            }
            document.getElementById('result-container').classList.remove('hidden');
            
            document.getElementById('result-icon').textContent = '✓';
            document.getElementById('result-title').textContent = data.already ? 'SUDAH HADIR' : 'HADIR';
            document.getElementById('result-subtitle').textContent = data.name;
        }

        function showError(msg) {
            document.body.className = 'state-error flex items-center justify-center min-h-screen p-6';
            document.getElementById('form-container').classList.add('hidden');
            if (document.getElementById('list-container')) {
                document.getElementById('list-container').classList.add('hidden');
            }
            document.getElementById('result-container').classList.remove('hidden');
            
            document.getElementById('result-icon').textContent = '✕';
            document.getElementById('result-title').textContent = 'GAGAL';
            document.getElementById('result-subtitle').textContent = msg;
        }

        function resetUI() {
            document.body.className = 'state-waiting flex items-center justify-center min-h-screen p-6';
            document.getElementById('form-container').classList.remove('hidden');
            document.getElementById('result-container').classList.add('hidden');
            if (document.getElementById('list-container')) {
                document.getElementById('list-container').classList.add('hidden');
            }
            document.getElementById('wa-input').value = '';
            // Clear URL param without reload
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>
