const params = new URLSearchParams(window.location.search);
const poId = params.get("id");

document.addEventListener("DOMContentLoaded", loadPO);

function loadPO() {
    fetch(`get_po_for_edit.php?id=${poId}`)
        .then(res => res.json())
        .then(data => {

            // Header
            document.getElementById("voucher").value =
                data.header.voucher_no;

            document.getElementById("poDate").value =
                data.header.po_date;

            document.getElementById("supplier").value =
                data.header.supplier_name;

            // Items
            const body = document.getElementById("itemBody");
            body.innerHTML = "";

            data.items.forEach((item, i) => {
                body.innerHTML += `
                    <tr>
                        <!-- ✅ FIXED S.NO -->
                        <td>${i + 1}</td>

                        <td>${item.description}</td>

                        <td>
                            <input type="number"
                                   value="${item.quantity}">
                        </td>
                    </tr>
                `;
            });
        })
        .catch(err => {
            console.error(err);
            alert("Failed to load PO details");
        });
}

function updatePO() {
    alert("Next step: save update (we’ll do this next)");
}
