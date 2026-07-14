<div class="tab-pane fade" id="isp">
    <form method="POST" action="{{ route('whatsapp.update') }}">
        @csrf
        <div class="mb-3">
            <label>{{ $templateDefs['whatsapp_isp_bill_template']['label'] }}</label>
            <textarea class="form-control mb-3" rows="6" name="whatsapp_isp_bill_template">{{ $settings['whatsapp_isp_bill_template']->value }}</textarea>
        </div>
        <div class="mb-3">
            <label>{{ $templateDefs['whatsapp_isp_reminder_template']['label'] }}</label>
            <textarea class="form-control mb-3" rows="6" name="whatsapp_isp_reminder_template">{{ $settings['whatsapp_isp_reminder_template']->value }}</textarea>
        </div>
        <div class="mb-3">
            <label>{{ $templateDefs['whatsapp_isp_payment_success_template']['label'] }}</label>
            <textarea class="form-control mb-3" rows="6" name="whatsapp_isp_payment_success_template">{{ $settings['whatsapp_isp_payment_success_template']->value }}</textarea>
        </div>
        <div class="mb-3">
            <label>{{ $templateDefs['whatsapp_isp_suspend_template']['label'] }}</label>
            <textarea class="form-control mb-3" rows="6" name="whatsapp_isp_suspend_template">{{ $settings['whatsapp_isp_suspend_template']->value }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">
            Save ISP Templates
        </button>
    </form>
</div>
