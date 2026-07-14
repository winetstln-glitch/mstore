<div class="tab-pane fade" id="test">
    <form method="POST" action="{{ route('whatsapp.test') }}">
        @csrf
        <div class="mb-3">
            <label>Phone Number</label>
            <input type="text" class="form-control mb-3" name="test_phone" placeholder="628xxxxxxxxxx">
        </div>
        <div class="mb-3">
            <label>Select Template</label>
            <select class="form-select mb-3" name="test_mode">
                <option value="plain">Plain</option>
                <option value="atk_receipt">ATK Receipt</option>
                <option value="wash_receipt">Wash Receipt</option>
                <option value="isp_bill">ISP Bill</option>
            </select>
        </div>
        <button type="submit" class="btn btn-dark">
            <i class="fa-solid fa-paper-plane"></i>
            Send Test
        </button>
    </form>
</div>
