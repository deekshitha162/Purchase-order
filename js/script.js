/* ================= DOM READY ================= */
document.addEventListener("DOMContentLoaded", () => {
    window.goodsPanel = document.getElementById("goodsPanel");
    window.goodsBody = document.getElementById("goodsBody");
    window.supplier = document.getElementById("supplier");
    window.supplierSuggestions = document.getElementById("supplierSuggestions");

    // AUTO CREATE FIRST ROW
    if (goodsBody.rows.length === 0) {
        addRow();
    }

    document.addEventListener("click", (e) => {

        if (
            e.target.closest("button") ||
            e.target.closest(".action-item") ||
            e.target.closest(".action-group")
        ) {
            return;
        }

        if (!supplier.contains(e.target) && !supplierSuggestions.contains(e.target)) {
            supplierSuggestions.style.display = "none";
            sIndex = -1;
        }

        document.querySelectorAll(".desc-input").forEach(input => {
            const box = input.nextElementSibling;
            if (!input.contains(e.target) && !box.contains(e.target)) {
                box.style.display = "none";
            }
        });
    });
});


/* ================= FINANCIAL YEAR FUNCTION ================= */
function getFinancialYear() {

    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth() + 1;

    if (month >= 4) {
        const y1 = year.toString().slice(-2);
        const y2 = (year + 1).toString().slice(-2);
        return y1 + y2;
    } else {
        const y1 = (year - 1).toString().slice(-2);
        const y2 = year.toString().slice(-2);
        return y1 + y2;
    }
}


/* ================= ADD / DELETE ROW ================= */
function addRow(after = null) {
    const row = document.createElement("tr");

    row.innerHTML = `
        <td></td>
        <td style="position:relative">
            <input type="text" class="desc-input" autocomplete="off">
            <div class="suggestions"></div>
        </td>
        <td><input type="number" min="1" step="1"></td>
        <td>
            <select>
                <option value="">Select</option>
                <option>MTR</option>
                <option>NO'S</option>
                <option>CM</option>
                <option>DOZ</option>
                <option>g</option>
                <option>KG</option>
                <option>LENGTH</option>
                <option>LTRS</option>
                <option>ML</option>
                <option>PAIR</option>
                <option>PCs</option>
                <option>Roll</option>
                <option>SET</option>
                <option>sq.FT</option>
            </select>
        </td>
        <td class="action-cell">
            <div class="action-group">
                <div class="action-item add-btn">
                    <span class="icon-add">+</span>
                    <div class="action-text">Add row</div>
                </div>
                <div class="action-item del-btn">
                    <span class="icon-del">🗑</span>
                    <div class="action-text">Delete row</div>
                </div>
            </div>
        </td>
    `;

    after ? goodsBody.insertBefore(row, after.nextSibling) : goodsBody.appendChild(row);

    row.querySelector(".add-btn").onclick = () => addRow(row);
    row.querySelector(".del-btn").onclick = () => deleteRow(row);

    attachDescription(row.querySelector(".desc-input"));
    renumber();
}

function deleteRow(row) {
    if (goodsBody.rows.length > 1) {
        row.remove();
        renumber();
    } else {
        alert("At least one row is required");
    }
}

function renumber() {
    [...goodsBody.rows].forEach((r, i) => {
        r.cells[0].innerText = i + 1;
    });
}


/* ================= SUPPLIER SUGGEST ================= */
let sIndex = -1;

supplier.addEventListener("input", () => {
    const q = supplier.value.trim();

    if (!q) {
        supplierSuggestions.style.display = "none";
        return;
    }

    fetch("../supplier_suggest.php?q=" + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            supplierSuggestions.innerHTML = "";
            sIndex = -1;

            if (!data.length) {
                supplierSuggestions.style.display = "none";
                return;
            }

            data.forEach(v => {
                const div = document.createElement("div");
                div.textContent = v;
                div.onclick = () => {
                    supplier.value = v;
                    supplierSuggestions.style.display = "none";
                };
                supplierSuggestions.appendChild(div);
            });

            supplierSuggestions.style.display = "block";
        });
});


/* ================= DESCRIPTION SUGGEST ================= */
function attachDescription(input) {
    const box = input.nextElementSibling;

    input.addEventListener("input", () => {

        const q = input.value.trim();

        if (!q) {
            box.style.display = "none";
            return;
        }

        fetch("../description_search.php?q=" + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {

                box.innerHTML = "";

                if (!data.length) {
                    box.style.display = "none";
                    return;
                }

                data.forEach(v => {

                    const div = document.createElement("div");
                    div.textContent = v;

                    div.onclick = () => {
                        input.value = v;
                        box.style.display = "none";
                    };

                    box.appendChild(div);
                });

                box.style.display = "block";
            });
    });
}


/* ================= CONFIRM MODAL ================= */
function savePurchaseOrder() {

    const voucherInput = document.getElementById("voucher").value.trim();

    // FINANCIAL YEAR AUTO
    const voucher = "POR/" + getFinancialYear() + "/" + voucherInput;

    const date = document.getElementById("poDate").value;
    const supplierName = supplier.value.trim();
    const warningBox = document.getElementById("voucherWarning");

    if (!voucherInput || !date || !supplierName) {
        alert("Fill all required fields");
        return;
    }

    let rowsHTML = "";
    let items = [];

    [...goodsBody.rows].forEach((row, i) => {

        const desc = row.cells[1].querySelector("input").value.trim();
        const qty = row.cells[2].querySelector("input").value;
        const per = row.cells[3].querySelector("select").value;

        if (desc && qty && per) {

            items.push({ description: desc, qty, per });

            rowsHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${desc}</td>
                    <td>${qty}</td>
                    <td>${per}</td>
                </tr>
            `;
        }
    });

    if (!items.length) {
        alert("Add at least one item");
        return;
    }

    fetch("../check_voucher.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ voucher })
    })
    .then(res => res.json())
    .then(data => {

        if (data.status === "duplicate") {

            warningBox.style.display = "block";
            warningBox.innerText = "⚠ This voucher number is already used";
            return;
        }

        warningBox.style.display = "none";

        document.getElementById("confirmContent").innerHTML = `
            <p><b>Voucher:</b> ${voucher}</p>
            <p><b>Date:</b> ${date}</p>
            <p><b>Supplier:</b> ${supplierName}</p>
            <table border="1" width="100%" style="border-collapse:collapse;margin-top:8px">
                <tr>
                    <th>S.No</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Per</th>
                </tr>
                ${rowsHTML}
            </table>
        `;

        window.confirmItems = { voucher, date, supplier: supplierName, items };

        document.getElementById("confirmModal").style.display = "flex";
    });
}


/* ================= CONFIRM SAVE ================= */
function confirmSave() {

    fetch("../save_purchase_order.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(window.confirmItems)
    })
    .then(res => res.json())
    .then(data => {

        if (data.status === "success") {

            alert("Purchase Order saved successfully!");

            goodsBody.innerHTML = "";
            addRow();

            document.getElementById("voucher").value = "";
            document.getElementById("poDate").value = "";
            supplier.value = "";

            closeConfirm();
        } 
        else {
            alert("Error saving data");
        }
    });
}


/* ================= CLOSE MODAL ================= */
function closeConfirm() {
    document.getElementById("confirmModal").style.display = "none";
}


/* ================= SMART BACK ================= */
function smartBack() {

    const voucher = document.getElementById("voucher");
    const poDate = document.getElementById("poDate");
    const supplierInput = document.getElementById("supplier");

    if (goodsBody.rows.length > 1) {
        goodsBody.deleteRow(goodsBody.rows.length - 1);
        renumber();
        return;
    }

    const row = goodsBody.rows[0];
    const desc = row.cells[1].querySelector("input");
    const qty = row.cells[2].querySelector("input");
    const per = row.cells[3].querySelector("select");

    if (desc.value || qty.value || per.value) {
        desc.value = "";
        qty.value = "";
        per.value = "";
        return;
    }

    if (voucher.value || poDate.value || supplierInput.value) {
        voucher.value = "";
        poDate.value = "";
        supplierInput.value = "";
        return;
    }

    window.history.back();
}