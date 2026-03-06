<?php
include "db.php";

$po_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT description, quantity, per FROM purchase_order_items WHERE po_id = $po_id";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice Entry</title>

<style>
body{font-family:Arial;background:#f4f6f8;margin:0}
.main-wrapper{display:flex;justify-content:center;gap:40px;margin-top:70px}
.container{width:650px;background:#fff;padding:30px;border-radius:8px;box-shadow:0 0 10px #ccc}
.side-panel{width:360px;background:#fff;padding:25px;border-radius:8px;display:none;box-shadow:0 0 10px #ccc}

.form-group{margin-bottom:18px}
label{font-weight:bold}
input,select,textarea{width:100%;padding:10px;margin-top:5px;border:1px solid #ccc;border-radius:4px}

button{padding:10px 20px;background:#5b1c1c;color:#fff;border:none;border-radius:5px;cursor:pointer}
button:hover{background:#3d1313}

.status-container{display:flex;gap:25px;margin-top:5px}
.text-success{color:#28a745;font-weight:bold}
.text-danger{color:#dc3545;font-weight:bold}

.qty-unit{display:flex;border:1px solid #ccc;border-radius:4px}
.qty-unit input{border:none;flex:1}
.qty-unit span{width:80px;background:#eee;text-align:center;padding:10px}

.new-section{width:90%;margin:40px auto;background:#fff;padding:25px;border-radius:8px;display:none}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ccc;padding:10px;text-align:center}
th{background:#5b1c1c;color:#fff}

.btn-delete{background:#dc3545;color:#fff;padding:5px 10px;border-radius:4px}
</style>
</head>

<body>

<div class="main-wrapper">

<div class="container">
<h2>Invoice Entry</h2>

<div class="form-group">
<label>Supplier Invoice Number *</label>
<input type="text" id="invoice_number" autocomplete="off">
</div>

<div class="form-group">
<label>Choose Item *</label>
<select id="item_id">
<option value="">-- Select Item --</option>
<?php while($row=mysqli_fetch_assoc($result)){ ?>
<option value="<?= htmlspecialchars($row['description']) ?>"
        data-qty="<?= $row['quantity'] ?>"
        data-per="<?= $row['per'] ?>">
    <?= htmlspecialchars($row['description']) ?>
</option>
<?php } ?>
</select>
</div>

<button onclick="openPanel()">Proceed</button>
</div>

<div class="side-panel" id="panel">

<div class="form-group">
<label>Status *</label>
<div class="status-container">
<label class="text-success">
<input type="radio" name="status" value="Accepted"> Accepted
</label>
<label class="text-danger">
<input type="radio" name="status" value="Rejected"> Rejected
</label>
</div>
</div>

<div class="form-group">
<label>Received Qty</label>
<div class="qty-unit">
<input type="number" id="received_qty">
<span id="unit">-</span>
</div>
</div>

<div class="form-group">
<label>Remarks</label>
<textarea id="remarks"></textarea>
</div>

<button onclick="addToList()">Add to List</button>

</div>
</div>

<div class="new-section" id="summary">
<h3>Invoice Summary</h3>

<table>
<thead>
<tr>
<th>Invoice</th>
<th>Description</th>
<th>PO Qty</th>
<th>Received</th>
<th>Pending</th>
<th>Status</th>
<th>Remarks</th>
<th>Action</th>
</tr>
</thead>
<tbody id="summaryBody"></tbody>
</table>

<br>
<label>Updated By *</label>
<input type="text" id="updated_by" autocomplete="off">
<br><br>
<button onclick="saveGRN()">Submit & Save GRN</button>
</div>

<script>

const poId = "<?= $po_id ?>";
let grnData = [];

/* load existing GRN */
fetch("get_grn.php?id="+poId)
.then(r=>r.json())
.then(d=>{
    grnData=d;
    markCompletedItems();
});

/* show unit */
item_id.onchange = ()=>{

    let unitName = item_id.selectedOptions[0]?.dataset.per || "-";
    unit.innerText = unitName;

    const decimalUnits = ["MTR","CM","KG","LTRS","ML","SQ.FT","LENGTH","G"];

    if(decimalUnits.includes(unitName.toUpperCase())){
        received_qty.step = "0.01";
    }else{
        received_qty.step = "1";
    }

};

/* disable qty if rejected */
document.querySelectorAll("input[name=status]").forEach(r=>{
    r.onchange=()=>{
        received_qty.disabled = r.value==="Rejected";
        received_qty.value="";
    };
});

/* open panel */
function openPanel(){
    if(!invoice_number.value || !item_id.value){
        alert("Fill Invoice & Item");
        return;
    }
    panel.style.display="block";
}

/* total received */
function getAlreadyReceived(desc){

    let total=0;

    grnData.forEach(r=>{
        if(r.description===desc && r.status==="Accepted"){
            total += Number(r.received_qty);
        }
    });

    document.querySelectorAll("#summaryBody tr").forEach(r=>{
        let d=r.children[1].innerText;
        let rec=r.children[3].innerText;
        let st=r.children[5].innerText;

        if(d===desc && st==="Accepted"){
            total += Number(rec);
        }
    });

    return total;
}

/* completed check */
function isCompleted(desc,poQty){
    return getAlreadyReceived(desc) >= poQty;
}

/* mark completed items */
function markCompletedItems(){

    document.querySelectorAll("#item_id option").forEach(opt=>{

        if(!opt.value) return;

        let desc = opt.value;
        let poQty = Number(opt.dataset.qty);

        if(getAlreadyReceived(desc) >= poQty){

            opt.text = desc + " (COMPLETED)";
            opt.disabled = true;

        }

    });

}

/* add to table */
function addToList(){

    let item=item_id.selectedOptions[0];
    let poQty=Number(item.dataset.qty);
    let desc=item.value;
    let unitName=item.dataset.per;

    if(isCompleted(desc,poQty)){
        alert("⚠ Item already COMPLETED. Pending is 0.");
        return;
    }

    let status=document.querySelector("input[name=status]:checked");
    if(!status){ alert("Select Status"); return; }

    let already=getAlreadyReceived(desc);

    let received=0;
    let pending=poQty;

    if(status.value==="Accepted"){

        received=Number(received_qty.value);

        const integerUnits = ["NO'S","PCS","PAIRS","DOZ","ROLL","SET"];

        if(integerUnits.includes(unitName.toUpperCase()) && !Number.isInteger(received)){
            alert("Decimal values not allowed for "+unitName);
            return;
        }

        if(!received || received<=0 || received>(poQty-already)){
            alert("Invalid Received Qty");
            return;
        }

        pending = poQty - already - received;
    }

    summary.style.display="block";

    summaryBody.innerHTML += `
    <tr>
        <td>${invoice_number.value}</td>
        <td>${desc}</td>
        <td>${poQty}</td>
        <td>${status.value==="Accepted"?received:0}</td>
        <td>${status.value==="Accepted"?pending:poQty-already}</td>
        <td>${status.value}</td>
        <td>${remarks.value}</td>
        <td><button class="btn-delete" onclick="this.closest('tr').remove()">X</button></td>
    </tr>`;

    received_qty.value="";
    remarks.value="";
    document.querySelectorAll("input[name=status]").forEach(r=>r.checked=false);

    markCompletedItems();
}

/* save GRN */
function saveGRN(){

    if(!updated_by.value){
        alert("Enter Updated By");
        return;
    }

    let send=[];

    document.querySelectorAll("#summaryBody tr").forEach(r=>{

        let c=r.children;

        send.push({
            po_id:poId,
            description:c[1].innerText,
            qty:c[2].innerText,
            received_qty:c[3].innerText,
            pending:c[4].innerText,
            status:c[5].innerText,
            remarks:c[6].innerText,
            updated_by:updated_by.value,
            supplier_invoice_number:c[0].innerText
        });

    });

    fetch("save_grn.php",{
        method:"POST",
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(send)
    })
    .then(r=>r.json())
    .then(res=>{
        if(res.status==="success"){
            alert("GRN Saved Successfully");
            location.href="html/po_view.html?id="+poId;
        }else{
            alert("Save Failed");
        }
    });
}

</script>

</body>
</html>