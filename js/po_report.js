let poTable = null;
let fullData = [];   // 🔴 STORE FULL DATA

/* ================= PAGE LOAD ================= */
document.addEventListener("DOMContentLoaded", () => {
    loadAll();

    const supplierInput = document.getElementById("searchName");
    const voucherInput  = document.getElementById("searchVoucher");

    if (supplierInput) {
        supplierInput.addEventListener("keydown", e =>
            handleKeyNavigation(e, "supplierSuggestions")
        );
        supplierInput.addEventListener("keyup", e => {
            if (isNavKey(e)) return;
            supplierSuggest();
        });
    }

    if (voucherInput) {
        voucherInput.addEventListener("keydown", e =>
            handleKeyNavigation(e, "voucherSuggestions")
        );
        voucherInput.addEventListener("keyup", e => {
            if (isNavKey(e)) return;
            voucherSuggest();
        });
    }

    document.addEventListener("click", e => {
        if (!e.target.closest(".search-box")) {
            hideBox("supplierSuggestions");
            hideBox("voucherSuggestions");
        }
    });
});

/* ================= LOAD ================= */
function loadAll() {
    fetchList({});
}

/* ================= SEARCH ================= */
function searchBySupplier() {
    const name = document.getElementById("searchName").value.trim();
    if (!name) return alert("Please enter supplier name");
    clearVoucher(); clearDates();
    fetchList({ name });
}

function applyVoucherSearch() {
    const voucher = document.getElementById("searchVoucher").value.trim();
    if (!voucher) return alert("Please enter voucher number");
    clearSupplier(); clearDates();
    fetchList({ voucher });
}

function applyDateFilter() {
    const from = document.getElementById("fromDate").value;
    const to   = document.getElementById("toDate").value;
    if (!from && !to) return alert("Please select From or To date");
    clearSupplier(); clearVoucher();
    fetchList({ from, to });
}

/* ================= FETCH ================= */
function fetchList(params) {
    const query = new URLSearchParams(params).toString();

    fetch(`../get_po_list.php?${query}`)
        .then(res => res.json())
        .then(data => {

            fullData = Array.isArray(data) ? data : []; // ✅ SAVE DATA
            applyStatusFilter(); // ✅ FILTER AFTER LOAD
        })
        .catch(err => console.error("Fetch error:", err));
}

/* ================= STATUS FILTER ================= */
function applyStatusFilter() {
    const status = document.getElementById("statusFilter")?.value || "ALL";

    let filtered = fullData;

    if (status !== "ALL") {
        filtered = fullData.filter(row => row.po_status === status);
    }

    renderTable(filtered);
}

/* ================= RENDER TABLE ================= */
function renderTable(data) {

    if (poTable) {
        poTable.destroy();
        poTable = null;
    }

    const tbody = document.getElementById("reportBody");
    tbody.innerHTML = "";

    if (!data.length) {
        tbody.innerHTML =
            `<tr><td colspan="5" style="text-align:center">No records found</td></tr>`;
        return;
    }

    data.forEach((row, i) => {

        let date = row.po_date
            ? row.po_date.split("-").reverse().join("-")
            : "";

        const color = row.po_status === "FINISHED" ? "green" : "red";

        tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td style="color:${color};font-weight:bold">${row.voucher_no}</td>
                <td style="color:${color};font-weight:bold">${date}</td>
                <td style="color:${color};font-weight:bold">${row.supplier_name}</td>
                <td>
                    <button class="view-btn" onclick="viewPO(${row.id})">View</button>
                </td>
            </tr>
        `;
    });

    initDataTable();
}

/* ================= DATATABLE ================= */
function initDataTable() {
    poTable = $('#reportTable').DataTable({
        paging: false,
        ordering: true,
        info: false,
        searching: false,
        dom: 'rt'
    });
}

/* ================= SUGGEST ================= */
function supplierSuggest() {
    suggestCommon("searchName", "supplierSuggestions", "../supplier_suggest.php", true);
}

function voucherSuggest() {
    suggestCommon("searchVoucher", "voucherSuggestions", "../voucher_suggest.php", false);
}
/* ================= HELPERS ================= */
function suggestCommon(inputId, boxId, url, isSupplier) {
    const input = document.getElementById(inputId);
    const box   = document.getElementById(boxId);
    const value = input.value.trim();

    if (value.length < 1) return hideBox(boxId);

    fetch(`${url}?term=${encodeURIComponent(value)}`)
        .then(res => res.json())
        .then(data => {
            box.innerHTML = "";
            box.dataset.index = "-1";

            if (!Array.isArray(data) || !data.length) return hideBox(boxId);

            data.forEach(text => {
                const div = document.createElement("div");
                div.textContent = text;
                div.addEventListener("mousedown", () => {
                    input.value = text;
                    hideBox(boxId);
                    isSupplier ? searchBySupplier() : applyVoucherSearch();
                });
                box.appendChild(div);
            });
            box.style.display = "block";
        });
}

function handleKeyNavigation(e, boxId) {
    const box = document.getElementById(boxId);
    if (!box || box.style.display !== "block") return;

    const items = box.querySelectorAll("div");
    if (!items.length) return;

    let index = parseInt(box.dataset.index || "-1");

    if (e.key === "ArrowDown") index = (index + 1) % items.length;
    else if (e.key === "ArrowUp") index = (index - 1 + items.length) % items.length;
    else if (e.key === "Enter") {
        e.preventDefault();
        if (index >= 0) items[index].dispatchEvent(new MouseEvent("mousedown"));
        return;
    } else return;

    items.forEach(el => el.classList.remove("active"));
    items[index].classList.add("active");
    box.dataset.index = index;
}

function isNavKey(e) {
    return ["ArrowUp", "ArrowDown", "Enter", "Escape"].includes(e.key);
}

function hideBox(id) {
    const box = document.getElementById(id);
    if (box) box.style.display = "none";
}

function clearSupplier() {
    document.getElementById("searchName").value = "";
}
function clearVoucher() {
    document.getElementById("searchVoucher").value = "";
}
function clearDates() {
    document.getElementById("fromDate").value = "";
    document.getElementById("toDate").value = "";
}

function refreshPage() {
    location.reload();
}

/* ================= NAV ================= */
function viewPO(id) {
    window.location.href = `../html/po_view.html?id=${id}`;
}