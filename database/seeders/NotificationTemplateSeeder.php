<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Ticket Group Notifications (WhatsApp & Telegram)
        Setting::updateOrCreate(
            ['key' => 'ticket_created_whatsapp_template'],
            [
                'value' => "🎫 *TIKET BARU: {ticket_number}*\n\n" .
                    "📌 *Tipe:* {ticket_type}\n" .
                    "👤 *Pelanggan:* {customer_name}\n" .
                    "👷 *Teknisi:* {technician_names}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "⚡ *Prioritas:* {ticket_priority}\n" .
                    "📍 *Alamat:* {ticket_address}\n\n" .
                    "🔗 *Detail:* {ticket_url}\n\n" .
                    "🚀 _Sistem M-Store_",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi WhatsApp: Tiket Baru (Grup)',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'ticket_created_telegram_template'],
            [
                'value' => "🎫 <b>TIKET BARU: {ticket_number}</b>\n\n" .
                    "📌 <b>Tipe:</b> {ticket_type}\n" .
                    "👤 <b>Pelanggan:</b> {customer_name}\n" .
                    "👷 <b>Teknisi:</b> {technician_names}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "⚡ <b>Prioritas:</b> {ticket_priority}\n" .
                    "📍 <b>Alamat:</b> {ticket_address}\n\n" .
                    "🔗 <b>Detail:</b> {ticket_url}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi Telegram: Tiket Baru (Grup)',
            ]
        );

        // Ticket Assigned to Technician
        Setting::updateOrCreate(
            ['key' => 'ticket_assigned_whatsapp_template'],
            [
                'value' => "*TUGAS BARU (TICKET ASSIGNED)*\n\n" .
                    "Halo {technician_name},\n" .
                    "Anda telah ditugaskan untuk tiket berikut:\n\n" .
                    "🎫 *No Tiket:* {ticket_number}\n" .
                    "📝 *Subject:* {ticket_subject}\n" .
                    "👤 *Customer:* {customer_name}\n" .
                    "📍 *Lokasi:* {ticket_location}\n" .
                    "⚠️ *Prioritas:* {ticket_priority}\n" .
                    "📄 *Deskripsi:* {ticket_description}\n\n" .
                    "Segera proses tiket ini melalui link berikut:\n{ticket_url}",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi WhatsApp: Tiket Diberikan (Teknisi)',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'ticket_assigned_telegram_template'],
            [
                'value' => "<b>TUGAS BARU (TICKET ASSIGNED)</b>\n\n" .
                    "Halo {technician_name},\n" .
                    "Anda telah ditugaskan untuk tiket berikut:\n\n" .
                    "🎫 <b>No Tiket:</b> {ticket_number}\n" .
                    "📝 <b>Subject:</b> {ticket_subject}\n" .
                    "👤 <b>Customer:</b> {customer_name}\n" .
                    "📍 <b>Lokasi:</b> {ticket_location}\n" .
                    "⚠️ <b>Prioritas:</b> {ticket_priority}\n" .
                    "📄 <b>Deskripsi:</b> {ticket_description}\n\n" .
                    "Segera proses tiket ini melalui link berikut:\n{ticket_url}",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi Telegram: Tiket Diberikan (Teknisi)',
            ]
        );

        // Ticket Status Updated
        Setting::updateOrCreate(
            ['key' => 'ticket_status_updated_whatsapp_template'],
            [
                'value' => "🔄 *STATUS TIKET DIPERBARUI*\n\n" .
                    "🎫 *No Tiket:* {ticket_number}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "🔄 *Status Baru:* {new_status}\n" .
                    "👤 *Diperbarui Oleh:* {updated_by}\n\n" .
                    "🔗 *Detail:* {ticket_url}",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi WhatsApp: Status Tiket Diperbarui',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'ticket_status_updated_telegram_template'],
            [
                'value' => "🔄 <b>STATUS TIKET DIPERBARUI</b>\n\n" .
                    "🎫 <b>No Tiket:</b> {ticket_number}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "🔄 <b>Status Baru:</b> {new_status}\n" .
                    "👤 <b>Diperbarui Oleh:</b> {updated_by}\n\n" .
                    "🔗 <b>Detail:</b> {ticket_url}",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi Telegram: Status Tiket Diperbarui',
            ]
        );

        // Ticket Solved
        Setting::updateOrCreate(
            ['key' => 'ticket_solved_whatsapp_template'],
            [
                'value' => "✅ *TIKET SELESAI: {ticket_number}*\n\n" .
                    "👤 *Pelanggan:* {customer_name}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "🛠️ *Oleh:* {updated_by}\n" .
                    "🗒️ *Hasil:* {ticket_note}\n\n" .
                    "🚀 _Sistem M-Store_",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi WhatsApp: Tiket Selesai (Grup)',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'ticket_solved_telegram_template'],
            [
                'value' => "✅ <b>TIKET SELESAI: {ticket_number}</b>\n\n" .
                    "👤 <b>Pelanggan:</b> {customer_name}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "🛠️ <b>Oleh:</b> {updated_by}\n" .
                    "🗒️ <b>Hasil:</b> {ticket_note}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi Telegram: Tiket Selesai (Grup)',
            ]
        );

        // Ticket Assigned to Technician (Group Notification)
        Setting::updateOrCreate(
            ['key' => 'ticket_assigned_group_whatsapp_template'],
            [
                'value' => "🎫 *PENUGASAN TIKET: {ticket_number}*\n\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "👷 *Teknisi:* {technician_names}\n" .
                    "👤 *Oleh:* {updated_by}\n" .
                    "🔗 *Detail:* {ticket_url}\n\n" .
                    "🚀 _Sistem M-Store_",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi WhatsApp: Teknisi Ditugaskan (Grup)',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'ticket_assigned_group_telegram_template'],
            [
                'value' => "🎫 <b>PENUGASAN TIKET: {ticket_number}</b>\n\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "👷 <b>Teknisi:</b> {technician_names}\n" .
                    "👤 <b>Oleh:</b> {updated_by}\n" .
                    "🔗 <b>Detail:</b> {ticket_url}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
                'group' => 'notifications',
                'type' => 'textarea',
                'label' => 'Template Notifikasi Telegram: Teknisi Ditugaskan (Grup)',
            ]
        );

        // Add more templates later if needed
    }
}
