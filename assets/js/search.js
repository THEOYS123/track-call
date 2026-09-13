/**
 * DATABASE ENGINE v4.0.2
 * Mode: Production / Secure
 */

async function startSearch() {
    const input = document.getElementById("searchInput");
    const codeInput = document.getElementById("secretCode");
    const dash = document.getElementById("dashboard");
    const loading = document.getElementById("loading");
    const loadStatus = document.getElementById("loading-status");
    const mainInfo = document.getElementById("mainInfo");
    const resultsPanel = document.getElementById("resultsPanel");
    const statsPanel = document.getElementById("statsPanel");

    const query = input.value.trim();
    const secretCode = codeInput ? codeInput.value.trim() : "";
    if (!query) return;

    // UI State: Loading
    if(mainInfo) mainInfo.classList.add("hidden");
    dash.classList.add("hidden");
    loading.classList.remove("hidden");

    const steps = ["Initialising Socket...", "Scraping Global Index...", "Cross-matching NIK...", "Finalising Report..."];
    let stepIdx = 0;
    const interval = setInterval(() => {
        if(stepIdx < steps.length) {
            loadStatus.innerText = steps[stepIdx];
            stepIdx++;
        }
    }, 400);

    try {
        const formData = new FormData();
        formData.append("query", query);
        formData.append("secret_code", secretCode);

        const response = await fetch("search.php", { method: "POST", body: formData });
        const res = await response.json();

        setTimeout(() => {
            clearInterval(interval);
            loading.classList.add("hidden");
            dash.classList.remove("hidden");

            // Stats Panel
            statsPanel.innerHTML = `
                <div style="background:var(--bg-card); padding:15px; border-radius:12px; border:1px solid var(--border); margin-bottom:20px; font-family:monospace; font-size:0.85rem; border-left: 4px solid var(--primary);">
                    <div>Target : <span style="color:var(--primary)">${res.query}</span></div>
                    <div>Records Found : ${res.records_found || 0}</div>
                    <div>LIVE_DATASET Search Time : ${res.speed || 0} sec</div>
                    <div>Profiles Linked : ${res.profiles ? res.profiles.length : 0}</div>
                </div>
            `;

            if(res.records_found > 0) {
                renderResults(res);
            } else {
                resultsPanel.innerHTML = `<div style="text-align:center; padding:20px; color:var(--text-muted);">Data tidak ditemukan dalam index kami.</div>`;
            }
        }, 1200);

    } catch (err) {
        clearInterval(interval);
        loading.classList.add("hidden");
        if(mainInfo) mainInfo.classList.remove("hidden");
        console.error("System Error:", err);
        alert("CRITICAL ERROR: Connection to search.php failed.");
    }
}

function renderResults(data) {
    const resultsPanel = document.getElementById("resultsPanel");
    let html = "";

    // Peringatan Keamanan
    if (data.threats && data.threats.length > 0) {
        html += `
        <div style="background:rgba(239,68,68,0.1); border:1px solid #ef4444; padding:15px; border-radius:12px; margin-bottom:25px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <strong style="color:#ef4444;">⚠️ THREAT DETECTION ADVISORY</strong>
                <button onclick="document.getElementById('warn-list').classList.toggle('hidden')" style="background:none; border:none; color:var(--primary); cursor:pointer; font-size:0.8rem; text-decoration:underline;">SEMBUNYIKAN BAGIAN INI</button>
            </div>
            <ul id="warn-list" style="margin:10px 0 0 0; padding-left:20px; color:#ef4444; font-size:0.85rem;">
                ${data.threats.map(t => `<li>${t}</li>`).join('')}
            </ul>
        </div>`;
    }

    // Peringatan Sensor
    if (data.is_censored) {
        html += `
        <div style="background:rgba(234, 179, 8, 0.1); border:1px solid #eab308; padding:15px; border-radius:12px; margin-bottom:25px; color:#ca8a04; font-size:0.85rem;">
            <strong>🔒 DATA SENSITIF DISENSOR</strong><br>
            Anda mengakses mode publik. Data NIK, Nomor HP, Email, dan Nama disensor sebagian. Hubungi Telegram <a href="https://t.me/flood1233" target="_blank" style="color:#ca8a04; text-decoration:underline; font-weight:bold;">@flood1233</a> untuk mendapatkan kode rahasia.
        </div>`;
    }

    // Profile Cards
    data.profiles.forEach((p, idx) => {
        const r = p.records[0] || {};
        const ana = Object.values(p.nik_analysis)[0] || {};
        const nameUpper = p.primary_name.toUpperCase();

        const rawOutput = 
`IDENTITY PROFILE #${idx + 1}
------------------------
Target Name : ${nameUpper}

[ RECORDED DATA ]
------------------------
NIK   : ${r.nik || '-'}
Phone : ${r.phone || '-'}
Email : ${r.email || '-'}
DOB   : ${ana.birthdate || '-'}

[ NIK ANALYSIS ]
> ${r.nik || 'N/A'}
  Province : ${ana.province || '-'}
  City     : ${ana.city || '-'}
  Birth    : ${ana.birthdate || '-'} (${ana.gender || '-'})
  Region   : Authenticated

[ RELATIONSHIP GRAPH ]
[ ${nameUpper} ]
  ├── Phone : ${r.phone || '-'}
  └── NIK   : ${r.nik || '-'}
------------------------`;

        html += `
        <div class="result-card" style="background: var(--bg-card); padding: 20px; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border); padding-bottom:10px;">
                <h3 style="margin:0; font-size:1rem; color:var(--primary);">Intelligence Record #${idx + 1}</h3>
                <button class="copy-btn" style="background:var(--primary); color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; font-size:0.7rem; font-weight:bold;">📋 SALIN DATA RAW</button>
                <textarea class="raw-data-store" style="display:none;">${rawOutput}</textarea>
            </div>

            <div style="display:flex; flex-direction:column; gap:15px;">
                <div>
                    <small style="color:var(--text-muted); font-size:0.65rem; font-weight:bold; text-transform:uppercase;">Subject Name</small>
                    <div style="font-weight:700; font-size:1.1rem;">${p.primary_name}</div>
                </div>

                <div style="background:var(--bg-body); padding:15px; border-radius:10px; border:1px solid var(--border);">
                    <div style="margin-bottom:10px; border-bottom:1px solid var(--border); padding-bottom:5px;">
                        <small style="color:var(--text-muted); display:block; font-size:0.65rem;">NIK / IDENTITAS</small>
                        <strong style="font-family:monospace; color:${data.is_censored ? '#ca8a04' : 'inherit'}">${r.nik || '-'}</strong>
                    </div>
                    <div style="margin-bottom:10px; border-bottom:1px solid var(--border); padding-bottom:5px;">
                        <small style="color:var(--text-muted); display:block; font-size:0.65rem;">PHONE / WHATSAPP</small>
                        <strong style="color:${data.is_censored ? '#ca8a04' : 'inherit'}">${r.phone || '-'}</strong>
                    </div>
                    <div>
                        <small style="color:var(--text-muted); display:block; font-size:0.65rem;">EMAIL ADDRESS</small>
                        <strong style="color:${data.is_censored ? '#ca8a04' : 'inherit'}">${r.email || '-'}</strong>
                    </div>
                </div>

                <div>
                    <small style="color:var(--text-muted); font-size:0.65rem; font-weight:bold; text-transform:uppercase;">Geographic Analysis</small>
                    <div style="font-size:0.85rem; margin-top:5px; line-height:1.6;">
                        📌 <strong>${ana.city || '-'}</strong>, ${ana.province || '-'}<br>
                        📅 ${ana.birthdate || '-'} (${ana.gender || '-'})
                    </div>
                </div>
            </div>
        </div>`;
    });

    resultsPanel.innerHTML = html;

    // Logika Copy
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.onclick = function() {
            const container = this.parentElement;
            const text = container.querySelector('.raw-data-store').value;
            
            navigator.clipboard.writeText(text).then(() => {
                const oldText = this.innerText;
                this.innerText = "✅ RAW COPIED";
                setTimeout(() => { this.innerText = oldText; }, 1500);
            });
        };
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("searchInput");
    const code = document.getElementById("secretCode");
    if (input) input.addEventListener("keypress", (e) => { if (e.key === "Enter") startSearch(); });
    if (code) code.addEventListener("keypress", (e) => { if (e.key === "Enter") startSearch(); });
});

// ==========================================
// FITUR MODAL CEK KODE RAHASIA
// ==========================================
function toggleCodeModal() {
    const modal = document.getElementById('codeModal');
    if (modal.classList.contains('hidden')) {
        modal.classList.remove('hidden');
        document.getElementById('codeResult').style.display = 'none';
        document.getElementById('checkCodeInput').value = '';
    } else {
        modal.classList.add('hidden');
    }
}

async function checkCodeStatus() {
    const inputCode = document.getElementById('checkCodeInput').value.trim();
    const resultBox = document.getElementById('codeResult');
    
    if (!inputCode) return;
    
    resultBox.style.display = 'block';
    resultBox.innerHTML = '<div style="text-align:center;">Menganalisa kunci...</div>';

    try {
        const formData = new FormData();
        formData.append("code", inputCode);
        const response = await fetch("check_code.php", { method: "POST", body: formData });
        const res = await response.json();

        if (res.status === 'success') {
            const statusColor = res.state === 'active' ? '#10b981' : '#ef4444';
            resultBox.innerHTML = `
                <div style="margin-bottom:8px;">Kode : <strong style="color:var(--primary)">${inputCode}</strong></div>
                <div style="margin-bottom:8px;">Status : <span style="background:${statusColor}; color:white; padding:2px 8px; border-radius:5px; font-size:0.75rem; text-transform:uppercase;">${res.state}</span></div>
                <div style="margin-bottom:8px;">Limit Total : <strong>${res.limit}</strong> Kali</div>
                <div style="margin-bottom:8px;">Telah Digunakan : <strong>${res.used}</strong> Kali</div>
                <hr style="border:0; border-top:1px solid var(--border); margin:10px 0;">
                <div>SISA KUOTA : <strong style="color:var(--primary); font-size:1.1rem;">${res.sisa}</strong> Kali</div>
            `;
        } else {
            resultBox.innerHTML = `<div style="color:#ef4444; text-align:center; font-weight:bold;">❌ ${res.message}</div>`;
        }
    } catch (err) {
        resultBox.innerHTML = `<div style="color:#ef4444; text-align:center;">Gagal terhubung ke server.</div>`;
    }
}

// Fungsi Kontrol Modal Menu Garis Tiga
function toggleCodeModal() {
    const modal = document.getElementById('codeModal');
    modal.classList.toggle('hidden');
    // Reset info saat modal dibuka tutup
    document.getElementById('codeResult').style.display = 'none';
    document.getElementById('checkCodeInput').value = '';
}

// Fungsi Cek Detail Kode ke Server
async function checkCodeStatus() {
    const inputCode = document.getElementById('checkCodeInput').value.trim();
    const resultBox = document.getElementById('codeResult');
    
    if (!inputCode) return;
    
    resultBox.style.display = 'block';
    resultBox.innerHTML = '<div style="color:var(--primary); text-align:center;">Verifying Signature...</div>';

    try {
        const formData = new FormData();
        formData.append("code", inputCode);
        const response = await fetch("check_code.php", { method: "POST", body: formData });
        const res = await response.json();

        if (res.status === 'success') {
            const color = res.state === 'active' ? '#10b981' : '#ef4444';
            resultBox.innerHTML = `
                <div style="border-left: 3px solid ${color}; padding-left: 10px;">
                    <div style="font-size:0.7rem; color:var(--text-muted);">STATUS</div>
                    <div style="color:${color}; font-weight:bold; margin-bottom:10px;">${res.state.toUpperCase()}</div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">USAGE HISTORY</div>
                    <div style="font-weight:bold;">${res.used} / ${res.limit} Kali</div>
                    <div style="font-size:0.7rem; color:var(--text-muted); margin-top:10px;">REMAINING QUOTA</div>
                    <div style="font-size:1.2rem; color:var(--primary); font-weight:bold;">${res.sisa} Queries Left</div>
                </div>
            `;
        } else {
            resultBox.innerHTML = `<div style="color:#ef4444; text-align:center;">❌ ${res.message}</div>`;
        }
    } catch (err) {
        resultBox.innerHTML = `<div style="color:#ef4444; text-align:center;">Connection Error.</div>`;
    }
}
