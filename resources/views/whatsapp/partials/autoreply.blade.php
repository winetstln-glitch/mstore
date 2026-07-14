<div class="tab-pane fade" id="autoreply">
    <form method="POST" action="{{ route('whatsapp.update') }}">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-info">
                    <div class="fw-semibold mb-2">
                        <i class="fas fa-plug me-2"></i>
                        Webhook Configuration
                    </div>
                    <div class="mb-2">
                        <strong>URL Webhook:</strong>
                        <div class="input-group mt-1">
                            <input type="text" class="form-control font-monospace" 
                                   value="{{ route('api.whatsapp.webhook') }}" 
                                   id="webhookUrl" readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="copyWebhookUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Enable Auto Reply</label>
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="whatsapp_autoreply_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_autoreply_enabled" name="whatsapp_autoreply_enabled" value="1" {{ $settings['whatsapp_autoreply_enabled']->value == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="whatsapp_autoreply_enabled">Aktifkan Auto Reply</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Balasan untuk Keyword Tidak Dikenali</label>
                    <textarea class="form-control" rows="4" name="whatsapp_unknown_keyword_reply" id="unknownKeywordTpl">{{ $settings['whatsapp_unknown_keyword_reply']->value }}</textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-light border">
                    <div class="fw-semibold mb-2">
                        <i class="fas fa-robot me-2"></i>
                        Perintah Auto Reply (dibuat di Bot Builder)
                    </div>
                    <div class="text-muted">Semua menu yang Anda buat di Bot Builder akan otomatis aktif sebagai auto reply!</div>
                    <hr>
                    <div class="fw-semibold mb-2">Contoh Perintah:</div>
                    <div><code>halo</code> - Sapa bot</div>
                    <div><code>bantuan</code> - Tampilkan menu</div>
                    <div><code>paket internet</code> - Info paket internet</div>
                    <div><code>cctv</code> - Info pemasangan CCTV</div>
                    <div><code>wash</code> - Info jasa cuci kendaraan</div>
                    <div><code>atk</code> - Info produk ATK</div>
                    <div><code>wedding</code> - Info layanan pernikahan</div>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Simpan Konfigurasi Auto Reply
            </button>
        </div>
    </form>
</div>
