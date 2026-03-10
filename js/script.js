/* ================= DOM READY ================= */
document.addEventListener("DOMContentLoaded", () => {
    window.goodsBody = document.getElementById("goodsBody");
    const pdfInput = document.getElementById("po_pdf");

    if (pdfInput) {
        pdfInput.addEventListener("change", readPDF);
    }

    addRow();
});

function addRow() {
    const row = document.createElement("tr");

    row.innerHTML = `
        <td></td>
        <td>
            <input type="text" class="desc" readonly placeholder="Auto from PDF">
        </td>
        <td>
            <input type="number" class="qty" readonly placeholder="-">
        </td>
        <td>
            <input type="text" class="per" readonly placeholder="-">
        </td>
    `;

    goodsBody.appendChild(row);
    renumber();
}

/* ================= RENUMBER ================= */
function renumber() {
    [...goodsBody.rows].forEach((r, i) => {
        r.cells[0].innerText = i + 1;
    });
}

/* ================= PDF AUTO FILL ================= */
function readPDF() {
    const fileInput = document.getElementById("po_pdf");

    if (!fileInput || !fileInput.files.length) {
        alert("Please upload a PDF file");
        return;
    }

    const formData = new FormData();
    formData.append("pdf", fileInput.files[0]);

    fetch("../read_po_pdf.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.status !== "success") {
            alert("Unable to read PDF.");
            return;
        }

        document.getElementById("voucher").value = data.voucher || "";
        document.getElementById("poDate").value = data.date || "";
        document.getElementById("supplier").value = data.supplier || "";

        goodsBody.innerHTML = "";

        data.items.forEach(item => {

            const row = document.createElement("tr");

            row.innerHTML = `
                <td></td>
                <td>
                    <input type="text" class="desc" readonly value="${item.description}">
                </td>
                <td>
                    <input type="number" class="qty" readonly value="${item.qty}">
                </td>
                <td>
                    <input type="text" class="per" readonly value="${item.per}">
                </td>
            `;

            goodsBody.appendChild(row);
        });

        renumber();
    })
    .catch(err => {
        console.error("Fetch Error:", err);
        alert("Error reading PDF.");
    });
}

/* ================= SHOW CONFIRM POPUP ================= */
function showConfirmPopup(){

    const voucher = document.getElementById("voucher").value;
    const date = document.getElementById("poDate").value;
    const supplier = document.getElementById("supplier").value;

    if (!voucher) {
        alert("Please upload PDF first");
        return;
    }

    let html = `
    <p><b>Voucher No:</b> ${voucher}</p>
    <p><b>Date:</b> ${date}</p>
    <p><b>Supplier:</b> ${supplier}</p>

    <table style="width:100%; margin-top:10px; border-collapse:collapse;">
    <tr style="background:#eee">
        <th>S.No</th>
        <th>Description</th>
        <th>Qty</th>
        <th>Per</th>
    </tr>
    `;

    [...goodsBody.rows].forEach((row, i) => {

        const desc = row.cells[1].querySelector("input").value;
        const qty = row.cells[2].querySelector("input").value;
        const per = row.cells[3].querySelector("input").value;

        html += `
        <tr>
            <td>${i+1}</td>
            <td>${desc}</td>
            <td>${qty}</td>
            <td>${per}</td>
        </tr>
        `;
    });

    html += "</table>";

    document.getElementById("confirmContent").innerHTML = html;
    document.getElementById("confirmModal").style.display = "flex";
}

/* ================= CLOSE POPUP ================= */
function closeConfirm(){
    document.getElementById("confirmModal").style.display = "none";
}

/* ================= CONFIRM SAVE ================= */
function confirmSave(){
    savePurchaseOrder();
}

/* ================= SAVE PURCHASE ORDER ================= */
function savePurchaseOrder() {

    const voucher = document.getElementById("voucher").value.trim();
    const date = document.getElementById("poDate").value;
    const supplierName = document.getElementById("supplier").value.trim();

    let items = [];

    [...goodsBody.rows].forEach((row) => {

        const desc = row.cells[1].querySelector("input").value.trim();
        const qty = row.cells[2].querySelector("input").value;
        const per = row.cells[3].querySelector("input").value;

        if(desc && qty && per){
            items.push({
                description: desc,
                qty,
                per
            });
        }

    });

    fetch("../save_purchase_order.php", {

        method:"POST",
        headers:{"Content-Type":"application/json"},
        body:JSON.stringify({
            voucher,
            date,
            supplier:supplierName,
            items
        })

    })
    .then(res=>res.json())
    .then(data=>{

        if(data.status === "duplicate"){

            alert("⚠ This voucher number already exists");
            return;
        }

        if(data.status === "success"){

            alert("Purchase Order Saved Successfully");
            closeConfirm();
            location.reload();
            return;
        }

        alert("Error saving data");

    })
    .catch(err=>{
        console.error("Save Error:",err);
        alert("Connection error while saving");
    });

}

/* ================= BACK BUTTON ================= */
function smartBack() {
    window.history.back();
} 