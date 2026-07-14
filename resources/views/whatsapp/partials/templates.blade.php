<div class="tab-pane fade" id="template">
    <form method="POST" action="{{ route('whatsapp.update') }}">
        @csrf
        
        <div class="mb-4">
            <h5 class="mb-3"><i class="fa-solid fa-ticket me-2"></i>Notifikasi Tiket</h5>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">{{ $templateDefs['ticket_created_whatsapp_template']['label'] }}</label>
                    <textarea class="form-control" rows="8" name="ticket_created_whatsapp_template">{{ $settings['ticket_created_whatsapp_template']->value }}</textarea>
                    <div class="form-text">
                        Placeholder: @foreach($templateDefs['ticket_created_whatsapp_template']['placeholders'] as $ph)<code>{{ '{' . $ph . '}' }}</code> @if(!$loop->last), @endif @endforeach
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">{{ $templateDefs['ticket_assigned_whatsapp_template']['label'] }}</label>
                    <textarea class="form-control" rows="8" name="ticket_assigned_whatsapp_template">{{ $settings['ticket_assigned_whatsapp_template']->value }}</textarea>
                    <div class="form-text">
                        Placeholder: @foreach($templateDefs['ticket_assigned_whatsapp_template']['placeholders'] as $ph)<code>{{ '{' . $ph . '}' }}</code> @if(!$loop->last), @endif @endforeach
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">{{ $templateDefs['ticket_status_updated_whatsapp_template']['label'] }}</label>
                    <textarea class="form-control" rows="8" name="ticket_status_updated_whatsapp_template">{{ $settings['ticket_status_updated_whatsapp_template']->value }}</textarea>
                    <div class="form-text">
                        Placeholder: @foreach($templateDefs['ticket_status_updated_whatsapp_template']['placeholders'] as $ph)<code>{{ '{' . $ph . '}' }}</code> @if(!$loop->last), @endif @endforeach
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">{{ $templateDefs['ticket_solved_whatsapp_template']['label'] }}</label>
                    <textarea class="form-control" rows="8" name="ticket_solved_whatsapp_template">{{ $settings['ticket_solved_whatsapp_template']->value }}</textarea>
                    <div class="form-text">
                        Placeholder: @foreach($templateDefs['ticket_solved_whatsapp_template']['placeholders'] as $ph)<code>{{ '{' . $ph . '}' }}</code> @if(!$loop->last), @endif @endforeach
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">{{ $templateDefs['ticket_assigned_group_whatsapp_template']['label'] }}</label>
                    <textarea class="form-control" rows="8" name="ticket_assigned_group_whatsapp_template">{{ $settings['ticket_assigned_group_whatsapp_template']->value }}</textarea>
                    <div class="form-text">
                        Placeholder: @foreach($templateDefs['ticket_assigned_group_whatsapp_template']['placeholders'] as $ph)<code>{{ '{' . $ph . '}' }}</code> @if(!$loop->last), @endif @endforeach
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="my-4">
        
        <div class="mb-4">
            <h5 class="mb-3"><i class="fa-solid fa-receipt me-2"></i>Struk</h5>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label>{{ $templateDefs['whatsapp_atk_receipt_template']['label'] }}</label>
                    <textarea class="form-control" rows="12" name="whatsapp_atk_receipt_template" id="atkTpl">{{ $settings['whatsapp_atk_receipt_template']->value }}</textarea>
                </div>
                <div class="col-md-6">
                    <label>Live Preview</label>
                    <div class="border p-3" style="min-height:300px;" id="atkPreview"></div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <label>{{ $templateDefs['whatsapp_wash_receipt_template']['label'] }}</label>
                    <textarea class="form-control" rows="12" name="whatsapp_wash_receipt_template" id="washTpl">{{ $settings['whatsapp_wash_receipt_template']->value }}</textarea>
                </div>
                <div class="col-md-6">
                    <label>Live Preview</label>
                    <div class="border p-3" style="min-height:300px;" id="washPreview"></div>
                </div>
            </div>
        </div>
        
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                Update Template
            </button>
        </div>
    </form>
</div>
