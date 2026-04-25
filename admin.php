<?php
session_start();

$password = 'gs1234';

if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Password salah!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

$db_host = getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? '127.0.0.1');
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

if (!$logged_in) {
    // Show login form
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100 w-full max-w-sm">
        <h2 class="text-2xl font-black text-center mb-6 text-gray-800">Admin Login</h2>
        <?php if(isset($error)) echo "<p class='text-red-500 text-sm font-semibold text-center mb-4'>$error</p>"; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Masukkan Password" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-transparent mb-4" required>
            <button type="submit" class="w-full bg-gray-900 text-white font-bold py-3 rounded-lg hover:bg-black transition">Login Dashboard</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Admin - Syirkah Bisnis</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 p-6 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Dasbor Pendaftaran</h1>
            <a href="?logout=1" class="px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">Logout</a>
        </div>


        <!-- Tab Navigation -->
        <div class="flex gap-1 mb-8 bg-gray-200/50 p-1 rounded-xl w-fit border border-gray-100">
            <button onclick="switchTab('pendaftar')" id="tab-btn-pendaftar" class="px-8 py-2.5 rounded-lg text-sm font-black transition-all bg-white text-gray-900 shadow-sm border border-gray-100">📋 Daftar Peserta</button>
            <button onclick="switchTab('analitik')" id="tab-btn-analitik" class="px-8 py-2.5 rounded-lg text-sm font-black transition-all text-gray-500 hover:text-gray-700">📊 Analitik Situs</button>
        </div>

        <!-- Tab: Pendaftar -->
        <div id="section-pendaftar" class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex flex-col">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider h-8 flex items-start">Total Peserta</p>
                    <p id="stat-total" class="text-3xl font-black text-gray-900">–</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex flex-col">
                    <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider h-8 flex items-start">Confirmed</p>
                    <p id="stat-confirmed" class="text-3xl font-black text-emerald-600">–</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex flex-col">
                    <p class="text-xs font-bold text-amber-500 uppercase tracking-wider h-8 flex items-start">Pending</p>
                    <p id="stat-pending" class="text-3xl font-black text-amber-500">–</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50/50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 font-bold text-gray-600 uppercase text-xs tracking-wider">No.</th>
                                <th class="px-6 py-4 font-bold text-gray-600 uppercase text-xs tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 font-bold text-gray-600 uppercase text-xs tracking-wider">Nama Lengkap</th>
                                <th class="px-6 py-4 font-bold text-gray-600 uppercase text-xs tracking-wider">Kontak</th>
                                <th class="px-6 py-4 font-bold text-gray-600 uppercase text-xs tracking-wider">Bisnis</th>
                                <th class="px-6 py-4 font-bold text-gray-600 uppercase text-xs tracking-wider">Bukti Transfer</th>
                                <th class="px-6 py-4 font-bold text-gray-600 uppercase text-xs tracking-wider">Status</th>
                                <th class="px-6 py-4 font-bold text-gray-600 uppercase text-xs tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body" class="divide-y divide-gray-50">
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-400 font-medium">Memuat data peserta...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Analitik -->
        <div id="section-analitik" class="hidden space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition">
                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                    </div>
                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Total Page Views</p>
                    <h2 id="ana-page-view" class="text-5xl font-black text-gray-900 tracking-tighter">0</h2>
                    <p class="text-xs text-gray-400 mt-4 font-medium italic">*Jumlah halaman utama dibuka</p>
                </div>
                
                <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition">
                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M9 5v14l11-7L9 5z"/></svg>
                    </div>
                    <p class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-2">Klik Tombol Daftar</p>
                    <h2 id="ana-click-register" class="text-5xl font-black text-gray-900 tracking-tighter">0</h2>
                    <div class="flex items-center gap-2 mt-4">
                        <span id="ana-conv-rate" class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] font-bold">0% CR</span>
                        <p class="text-xs text-gray-400 font-medium italic">Conversion Rate</p>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition">
                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                    </div>
                    <p class="text-xs font-black text-emerald-500 uppercase tracking-widest mb-2">Klik Konfirmasi WA</p>
                    <h2 id="ana-click-wa" class="text-5xl font-black text-gray-900 tracking-tighter">0</h2>
                    <p class="text-xs text-gray-400 mt-4 font-medium italic">*Jumlah klik tombol WA Konfirmasi</p>
                </div>
            </div>

            <div class="bg-indigo-900 rounded-3xl p-8 text-white flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <h3 class="text-xl font-black mb-1 italic">Kenapa data ini penting?</h3>
                    <p class="text-indigo-200 text-sm max-w-xl">Semakin besar selisih antara Page View dan Klik Daftar, berarti banyak orang yang mampir tapi belum tertarik daftar. Anda mungkin perlu memperbaiki judul atau penawaran di halaman utama.</p>
                </div>
                <button onclick="loadStats()" class="px-6 py-3 bg-white text-indigo-900 font-black rounded-xl hover:bg-indigo-50 transition text-sm">Refresh Analitik</button>
            </div>
        </div>

    <!-- Modal Bukti Transfer -->
    <div id="image-modal" class="fixed inset-0 bg-black/90 hidden z-50 overflow-y-auto backdrop-blur-sm" style="display:none;">
        <div class="relative max-w-3xl w-full flex flex-col items-center mx-auto py-16 px-4 min-h-full">
            <button onclick="closeModal()" class="sticky top-4 self-end text-white/60 hover:text-white font-bold text-sm tracking-widest uppercase transition bg-black/40 px-3 py-1.5 rounded-lg mb-4 z-10">&times; Tutup</button>
            <img id="modal-image" src="" class="max-w-full w-full rounded-lg shadow-2xl" style="height:auto;" alt="Bukti Transfer">
            <a id="modal-download" href="" download class="mt-6 mb-4 px-6 py-2 bg-white text-black font-bold rounded-full text-sm hover:bg-gray-200 transition">Download Resi</a>
        </div>
    </div>

    <!-- Hidden input for manual upload -->
    <input type="file" id="manual-upload-input" style="display:none" accept="image/*,.pdf" onchange="handleManualUpload(this)">

    <!-- Modal Konfirmasi Hapus -->
    <div id="delete-modal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm p-4" style="display:none;">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 text-lg font-black flex-shrink-0">&times;</div>
                <div>
                    <h3 class="font-black text-gray-900 text-lg">Hapus Peserta</h3>
                    <p id="delete-modal-name" class="text-gray-500 text-sm"></p>
                </div>
            </div>
            <p class="text-gray-600 text-sm mb-5">Tindakan ini <strong>tidak dapat dibatalkan</strong>. Data peserta akan dihapus secara permanen dari database.</p>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Ketik <span class="text-red-600 font-black">hapus</span> untuk melanjutkan</label>
            <input id="delete-confirm-input" type="text" placeholder="hapus" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:border-red-400 mb-5 transition" autocomplete="off" />
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl text-sm hover:bg-gray-200 transition">Batal</button>
                <button id="delete-confirm-btn" onclick="confirmDelete()" class="flex-1 px-4 py-3 bg-red-600 text-white font-bold rounded-xl text-sm hover:bg-red-700 transition disabled:opacity-40 disabled:cursor-not-allowed" disabled>Hapus Permanen</button>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const pendaftar = document.getElementById('section-pendaftar');
            const analitik = document.getElementById('section-analitik');
            const btnPendaftar = document.getElementById('tab-btn-pendaftar');
            const btnAnalitik = document.getElementById('tab-btn-analitik');

            if (tab === 'pendaftar') {
                pendaftar.classList.remove('hidden');
                analitik.classList.add('hidden');
                btnPendaftar.className = "px-8 py-2.5 rounded-lg text-sm font-black transition-all bg-white text-gray-900 shadow-sm border border-gray-100";
                btnAnalitik.className = "px-8 py-2.5 rounded-lg text-sm font-black transition-all text-gray-500 hover:text-gray-700";
                loadData();
            } else {
                pendaftar.classList.add('hidden');
                analitik.classList.remove('hidden');
                btnAnalitik.className = "px-8 py-2.5 rounded-lg text-sm font-black transition-all bg-white text-gray-900 shadow-sm border border-gray-100";
                btnPendaftar.className = "px-8 py-2.5 rounded-lg text-sm font-black transition-all text-gray-500 hover:text-gray-700";
                loadStats();
            }
        }

        function loadStats() {
            fetch('api.php?action=stats')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const s = data.data;
                    document.getElementById('ana-page-view').textContent = (s.page_view || 0).toLocaleString();
                    document.getElementById('ana-click-register').textContent = (s.click_register || 0).toLocaleString();
                    document.getElementById('ana-click-wa').textContent = (s.click_wa || 0).toLocaleString();
                    
                    if (s.page_view > 0) {
                        const rate = ((s.click_register / s.page_view) * 100).toFixed(1);
                        document.getElementById('ana-conv-rate').textContent = rate + '% CR';
                    }
                }
            });
        }

        function loadData() {
            fetch('api.php')
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    renderTable(data.data);
                }
            });
        }

        function renderTable(rows) {
            const tbody = document.getElementById('table-body');
            tbody.innerHTML = '';
            
            if(rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-400 font-medium">Belum ada pendaftar</td></tr>';
                return;
            }

            rows.forEach((row, index) => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50/80 transition";
                
                // Status Badge styling
                let statusBadge = '';
                if(row.status === 'confirmed') statusBadge = '<span class="px-3 py-1 rounded-md bg-emerald-100 text-emerald-700 text-xs font-bold uppercase tracking-wider border border-emerald-200">Confirmed</span>';
                else if(row.status === 'tolak') statusBadge = '<span class="px-3 py-1 rounded-md bg-red-100 text-red-700 text-xs font-bold uppercase tracking-wider border border-red-200">Ditolak</span>';
                else statusBadge = '<span class="px-3 py-1 rounded-md bg-amber-100 text-amber-700 text-xs font-bold uppercase tracking-wider border border-amber-200">Pending</span>';

                // Proof link
                let proofLink = `
                    <div class="flex flex-col gap-1">
                        <span class="text-gray-400 italic text-xs font-medium bg-gray-100 px-2 py-1 rounded w-fit">Belum Upload</span>
                        <button onclick="triggerManualUpload('${row.whatsapp_number}')" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold text-left transition flex items-center gap-0.5">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Upload Manual
                        </button>
                    </div>
                `;
                if(row.payment_proof) {
                    const isPdf = row.payment_proof.toLowerCase().endsWith('.pdf');
                    if(isPdf) {
                        proofLink = `<a href="/uploads/${row.payment_proof}" target="_blank" class="text-blue-600 hover:text-blue-800 font-bold text-xs bg-blue-50 px-3 py-1.5 rounded-md border border-blue-100 transition inline-block">Buka PDF</a>`;
                    } else {
                        proofLink = `<button onclick="openModal('/uploads/${row.payment_proof}')" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-indigo-50 px-3 py-1.5 rounded-md border border-indigo-100 transition">Lihat Gambar</button>`;
                    }
                }

                // Date formatting
                const dateObj = new Date(row.created_at);
                const dateStr = dateObj.toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year:'numeric', hour:'2-digit', minute:'2-digit'});

                tr.innerHTML = `
                    <td class="px-6 py-4 text-gray-400 font-mono text-xs">${index + 1}</td>
                    <td class="px-6 py-4 text-gray-500 text-xs">${dateStr}</td>
                    <td class="px-6 py-4 font-bold text-gray-900">${row.full_name}</td>
                    <td class="px-6 py-4">
                        <a href="https://wa.me/${row.whatsapp_number.replace(/^0/, '62')}" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-bold text-sm flex items-center gap-1 group">
                            <svg class="w-4 h-4 group-hover:scale-110 transition" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                            ${row.whatsapp_number}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-gray-500 max-w-[200px] truncate text-xs" title="${row.business_activity}">${row.business_activity}</td>
                    <td class="px-6 py-4">${proofLink}</td>
                    <td class="px-6 py-4">${statusBadge}</td>
                    <td class="px-6 py-4 flex gap-2">
                        <button onclick="updateStatus(${row.id}, 'confirmed')" class="px-3 py-1.5 bg-gray-900 text-white hover:bg-black rounded border border-transparent text-xs font-bold transition">Confirm</button>
                        <button onclick="updateStatus(${row.id}, 'tolak')" class="px-3 py-1.5 bg-white text-red-600 hover:bg-red-50 rounded border border-red-200 text-xs font-bold transition">Tolak</button>
                        <button onclick="deleteRow(${row.id}, '${row.full_name}')" class="px-3 py-1.5 bg-red-600 text-white hover:bg-red-700 rounded border border-transparent text-xs font-bold transition" title="Hapus peserta">&times;</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Update statistik
            const total = rows.length;
            const confirmed = rows.filter(r => r.status === 'confirmed').length;
            const pending = rows.filter(r => r.status === 'pending' || !r.status || (r.status !== 'confirmed' && r.status !== 'tolak')).length;
            document.getElementById('stat-total').textContent = total;
            document.getElementById('stat-confirmed').textContent = confirmed;
            document.getElementById('stat-pending').textContent = pending;
        }

        let _uploadingWhatsapp = null;
        function triggerManualUpload(whatsapp) {
            _uploadingWhatsapp = whatsapp;
            document.getElementById('manual-upload-input').click();
        }

        function handleManualUpload(input) {
            if (!input.files || !input.files[0] || !_uploadingWhatsapp) return;
            
            const file = input.files[0];
            const formData = new FormData();
            formData.append('payment_proof', file);
            formData.append('whatsapp', _uploadingWhatsapp);

            // Overlay loading simple
            const btn = event ? event.target : null;
            
            fetch('upload_proof.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadData();
                } else {
                    alert('Gagal upload: ' + data.message);
                }
            })
            .catch(err => alert('Error: ' + err))
            .finally(() => {
                input.value = '';
                _uploadingWhatsapp = null;
            });
        }

        let _deleteId = null;

        function deleteRow(id, name) {
            _deleteId = id;
            document.getElementById('delete-modal-name').textContent = name;
            document.getElementById('delete-confirm-input').value = '';
            document.getElementById('delete-confirm-btn').disabled = true;
            document.getElementById('delete-modal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            setTimeout(() => document.getElementById('delete-confirm-input').focus(), 100);
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').style.display = 'none';
            document.body.style.overflow = '';
            _deleteId = null;
        }

        document.getElementById('delete-confirm-input').addEventListener('input', function() {
            document.getElementById('delete-confirm-btn').disabled = this.value.trim().toLowerCase() !== 'hapus';
        });

        function confirmDelete() {
            if (!_deleteId) return;
            const btn = document.getElementById('delete-confirm-btn');
            btn.textContent = 'Menghapus...';
            btn.disabled = true;

            fetch('api.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: _deleteId})
            })
            .then(res => res.json())
            .then(data => {
                closeDeleteModal();
                if(data.success) {
                    loadData();
                } else {
                    alert('Gagal menghapus: ' + data.message);
                }
            })
            .catch(err => { closeDeleteModal(); alert('Error: ' + err); });
        }

        function updateStatus(id, newStatus) {
            const statusLabel = newStatus === 'confirmed' ? 'CONFIRMED' : 'DITOLAK';
            if(!confirm(`Apakah Anda yakin mengubah status pendaftar ini menjadi ${statusLabel}?`)) return;

            fetch('api.php', {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id, status: newStatus})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    loadData();
                } else {
                    alert('Gagal mengubah status: ' + data.message);
                }
            })
            .catch(err => alert('Error: ' + err));
        }

        function openModal(src) {
            document.getElementById('modal-image').src = src;
            document.getElementById('modal-download').href = src;
            const modal = document.getElementById('image-modal');
            modal.style.display = 'block';
            modal.scrollTop = 0;
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('image-modal').style.display = 'none';
            document.body.style.overflow = '';
        }

        document.getElementById('image-modal').addEventListener('click', function(e) {
            if(e.target === this) closeModal();
        });

        loadData();
    </script>
</body>
</html>
