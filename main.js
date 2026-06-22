function openModal(id) {
    document.getElementById(id).classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

document.querySelectorAll('.modal-backdrop').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});

document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 4000);
});

function confirmDelete(url, nama) {
    if (confirm('Yakin hapus "' + nama + '"?')) {
        window.location.href = url;
    }
}

function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('keyup', function() {
        const keyword = this.value.toLowerCase();
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
        });
    });
}

function formatRupiah(angka) {
    return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
}

function hitungTotal() {
    const jumlah = parseInt(document.getElementById('jumlah')?.value) || 0;
    const harga  = parseFloat(document.getElementById('harga_satuan')?.value) || 0;
    const el     = document.getElementById('total_preview');
    if (el) el.textContent = formatRupiah(jumlah * harga);
}

document.getElementById('jumlah')?.addEventListener('input', hitungTotal);
document.getElementById('harga_satuan')?.addEventListener('input', hitungTotal);

filterTable('searchInput', 'mainTable');