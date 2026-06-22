<!DOCTYPE html>
<html>
<head>
    <title>Kasir POS</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body{
            font-family: Arial;
            padding:20px;
        }

        .card{
            border:1px solid #ddd;
            padding:15px;
            margin-top:15px;
            border-radius:8px;
        }
    </style>
</head>
<body>

<h2>Kasir POS</h2>

<!-- TAMBAHAN -->
<div id="reader" style="width:300px;"></div>
<br>

<input
    type="text"
    id="qr"
    placeholder="Scan / Input QR"
>

<button onclick="cariBarang()">
    Cari
</button>

<div id="hasil"></div>

<hr>
<h3> Keranjang</h3>
<div id ="cart"></div>
<h2 id = "total">
    Total : Rp 0
</h2>

<button onclick="bayar()">Bayar</button>

<!-- PINDAH KE SINI -->
<audio id="beepSound" preload="auto">
    <source src="beep.mp3" type="audio/mpeg">
</audio>

<script>
    let cart =[];
    let isScanning = true;

function renderCart(){

    let html = '';
    let total = 0;

    cart.forEach((item,index) => {

        total += item.harga * item.qty;

        html += `
            <div style="
                border:1px solid #ddd;
                padding:10px;
                margin-bottom:10px;
                border-radius:8px;
            ">

                <strong>${item.nama}</strong><br>

                Harga :
                Rp ${item.harga.toLocaleString()}
                <br><br>

                <button onclick="kurangQty(${index})">
                    -
                </button>

                <strong style="margin:0 10px">
                    ${item.qty}
                </strong>

                <button onclick="tambahQty(${index})">
                    +
                </button>

                <button
                    onclick="hapusItem(${index})"
                    style="
                        margin-left:10px;
                        background:red;
                        color:white;
                        border:none;
                        padding:5px 10px;
                        cursor:pointer;
                    "
                >
                    Hapus
                </button>

            </div>
        `;

    });

    document.getElementById('cart').innerHTML = html;

    document.getElementById('total').innerHTML =
        `Total : Rp ${total.toLocaleString()}`;
}

function kurangQty(index){

    if(cart[index].qty > 1){

        cart[index].qty--;

    }else{

        if(confirm("Hapus barang dari keranjang?")){

            cart.splice(index, 1);
        }
    }

    renderCart();
}

function hapusItem(index){

    if(confirm("Hapus barang ini?")){

        cart.splice(index, 1);

        renderCart();
    }
}
    function bayar(){

        if(cart.length === 0){
            alert("Keranjang kosong");
            return;
        }

        fetch("proses_bayar.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(cart)
        })
        .then(res => res.json())
        .then(result => {

            alert(result.message);

            if(result.success){

                cart = [];

                document.getElementById('hasil').innerHTML = '';
                document.getElementById('qr').value = '';

                renderCart();
            }

        });

    }

    function cariBarang(){

        const qr =
            document.getElementById('qr').value;

        fetch(`get_barang.php?qr=${qr}`)
        .then(res => res.json())
        .then(barang => {

            document.getElementById('hasil').innerHTML = `
                <div class="card">
                    <h3>${barang.nama_barang}</h3>
                    <p>Kode : ${barang.kode_barang}</p>
                    <p>Harga : Rp ${Number(barang.harga_jual).toLocaleString()}</p>
                    <p>Stok : ${barang.stok}</p>
                </div>
            `;

            const existing = cart.find(
                item => item.id == barang.id
            );

            if(existing){
                existing.qty++;
            }else{
                cart.push({
                    id: barang.id,
                    nama: barang.nama_barang,
                    harga: Number(barang.harga_jual),
                    qty: 1
                });
            }

            renderCart();

        });

    }

    function beep() {

        const audioCtx =
            new (window.AudioContext || window.webkitAudioContext)();

        const oscillator =
            audioCtx.createOscillator();

        const gainNode =
            audioCtx.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        oscillator.frequency.value = 1000;

        oscillator.type = "sine";

        gainNode.gain.value = 0.1;

        oscillator.start();

        setTimeout(() => {
            oscillator.stop();
        }, 120);

    }

    // TAMBAHAN SCANNER
    function onScanSuccess(decodedText){

        if(!isScanning) return;

        isScanning = false;

        const sound = document.getElementById('beepSound');

        sound.currentTime = 0;
        sound.play();

        document.getElementById('qr').value = decodedText;

        cariBarang();

        setTimeout(() => {
            isScanning = true;
        }, 2000);
    }

    // JALANKAN KAMERA
    const html5QrCode = new Html5Qrcode("reader");

    html5QrCode.start(
        { facingMode: "user" },
        {
            fps: 10,
            qrbox: 250
        },
        onScanSuccess
    ).catch(err => {
        console.log(err);
    });

</script>

</body>
</html>