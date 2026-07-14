import React, { useState } from 'react'; 
 import { 
   Printer, 
   Download, 
   Building2, 
   User, 
   Calendar, 
   Wallet, 
   TrendingDown, 
   CheckCircle2, 
   Clock, 
   ArrowUpRight, 
   ArrowDownRight, 
   Star 
 } from 'lucide-react'; 
 
 const Payslip = () => { 
   // Mengadaptasi data dengan rincian bonus yang lebih spesifik 
   const [data] = useState({ 
     perusahaan: { 
       nama: "MULTITECH SERVICE INDONESIA", 
       alamat: "Kawasan Industri Delta Silicon, Cikarang", 
       email: "finance@multitech.id" 
     }, 
     user: { 
       name: "Budi Santoso", 
       email: "budi.tech@gmail.com", 
       jabatan: "Teknisi Senior", 
       periode: "Mei 2024" 
     }, 
     absensi: [ 
       { tanggal: "2024-05-01", masuk: "08:00", pulang: "17:00", status: "present", catatan: "Normal" }, 
       { tanggal: "2024-05-02", masuk: "08:15", pulang: "17:05", status: "present", catatan: "Overtime 1 jam" }, 
       { tanggal: "2024-05-03", masuk: "08:00", pulang: "17:00", status: "present", catatan: "Normal" }, 
       { tanggal: "2024-05-04", masuk: "-", pulang: "-", status: "leave", catatan: "Izin Sakit" }, 
       { tanggal: "2024-05-05", masuk: "07:55", pulang: "17:00", status: "present", catatan: "Normal" } 
     ], 
     ringkasan: { 
       present_count: 22, 
       leave_count: 2, 
       paid_days: 24, 
       daily_salary: 250000, 
       bonus_rincian: { 
         disiplin: 200000, 
         tanggung_jawab: 200000, 
         absensi: 100000 
       }, 
       total_kasbon: 200000 
     } 
   }); 
 
   const formatCurrency = (num) => { 
     return new Intl.NumberFormat('id-ID', { 
       style: 'currency', 
       currency: 'IDR', 
       minimumFractionDigits: 0 
     }).format(num); 
   }; 
 
   const totalBonus = Object.values(data.ringkasan.bonus_rincian).reduce((a, b) => a + b, 0); 
   const gajiPokok = data.ringkasan.daily_salary * data.ringkasan.paid_days; 
   const totalGaji = gajiPokok + totalBonus - data.ringkasan.total_kasbon; 
 
   return ( 
     <div className="min-h-screen bg-slate-100 p-4 md:p-8 font-sans"> 
       <div className="max-w-5xl mx-auto space-y-6"> 
         
         {/* Kontrol Aksi */} 
         <div className="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-slate-200 print:hidden"> 
           <div> 
             <h1 className="text-xl font-bold text-slate-800">Slip Gaji Teknisi</h1> 
             <p className="text-sm text-slate-500">Periode: {data.user.periode}</p> 
           </div> 
           <button 
             onClick={() => window.print()} 
             className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-medium transition-all" 
           > 
             <Printer size={18} /> 
             Cetak Slip 
           </button> 
         </div> 
 
         {/* Area Slip Gaji */} 
         <div className="bg-white shadow-xl rounded-2xl overflow-hidden print:shadow-none border border-slate-200"> 
           
           {/* Header */} 
           <div className="p-8 border-b-4 border-indigo-600 flex flex-col md:flex-row justify-between gap-6 bg-slate-50"> 
             <div className="flex items-center gap-4"> 
               <div className="bg-indigo-600 p-3 rounded-xl shadow-lg text-white"> 
                 <Building2 size={32} /> 
               </div> 
               <div> 
                 <h2 className="text-2xl font-bold text-slate-800 tracking-tight">{data.perusahaan.nama}</h2> 
                 <p className="text-slate-500 text-sm">{data.perusahaan.alamat}</p> 
               </div> 
             </div> 
             <div className="md:text-right"> 
               <span className="inline-block px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold uppercase tracking-widest mb-2"> 
                 Official Payslip 
               </span> 
               <p className="text-slate-400 text-xs">Dicetak: {new Date().toLocaleString('id-ID')}</p> 
             </div> 
           </div> 
 
           {/* Info Teknisi */} 
           <div className="grid grid-cols-1 md:grid-cols-3 gap-6 p-8 bg-white border-b border-slate-100"> 
             <div className="flex items-center gap-3"> 
               <div className="p-2 bg-slate-100 rounded-lg text-slate-600"><User size={20}/></div> 
               <div> 
                 <p className="text-[10px] uppercase font-bold text-slate-400">Nama Teknisi</p> 
                 <p className="font-bold text-slate-800">{data.user.name}</p> 
                 <p className="text-xs text-slate-500">{data.user.email}</p> 
               </div> 
             </div> 
             <div className="flex items-center gap-3"> 
               <div className="p-2 bg-slate-100 rounded-lg text-slate-600"><Clock size={20}/></div> 
               <div> 
                 <p className="text-[10px] uppercase font-bold text-slate-400">Jabatan</p> 
                 <p className="font-bold text-slate-800">{data.user.jabatan}</p> 
               </div> 
             </div> 
             <div className="flex items-center gap-3"> 
               <div className="p-2 bg-slate-100 rounded-lg text-slate-600"><Calendar size={20}/></div> 
               <div> 
                 <p className="text-[10px] uppercase font-bold text-slate-400">Periode Gaji</p> 
                 <p className="font-bold text-slate-800">{data.user.periode}</p> 
               </div> 
             </div> 
           </div> 
 
           {/* Rincian Gaji & Bonus */} 
           <div className="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100"> 
             
             {/* Bagian Pendapatan */} 
             <div className="p-8 space-y-6"> 
               <h3 className="text-sm font-bold text-indigo-600 flex items-center gap-2 uppercase tracking-wider"> 
                 <Wallet size={16} /> Pendapatan & Bonus 
               </h3> 
               
               <div className="space-y-4"> 
                 {/* Gaji Pokok */} 
                 <div className="flex justify-between items-center text-sm"> 
                   <div className="text-slate-600"> 
                     <p className="font-medium">Gaji Harian</p> 
                     <p className="text-[10px] text-slate-400">{formatCurrency(data.ringkasan.daily_salary)} × {data.ringkasan.paid_days} hari</p> 
                   </div> 
                   <span className="font-bold text-slate-800">{formatCurrency(gajiPokok)}</span> 
                 </div> 
 
                 {/* Rincian Bonus */} 
                 <div className="pt-4 border-t border-slate-100 space-y-3"> 
                   <p className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Rincian Bonus</p> 
                   
                   <div className="flex justify-between items-center text-sm"> 
                     <div className="flex items-center gap-2 text-slate-600"> 
                       <Star size={14} className="text-amber-400 fill-amber-400" /> 
                       <span>Bonus Disiplin</span> 
                     </div> 
                     <span className="font-medium text-emerald-600">+{formatCurrency(data.ringkasan.bonus_rincian.disiplin)}</span> 
                   </div> 
 
                   <div className="flex justify-between items-center text-sm"> 
                     <div className="flex items-center gap-2 text-slate-600"> 
                       <Star size={14} className="text-amber-400 fill-amber-400" /> 
                       <span>Bonus Tanggung Jawab</span> 
                     </div> 
                     <span className="font-medium text-emerald-600">+{formatCurrency(data.ringkasan.bonus_rincian.tanggung_jawab)}</span> 
                   </div> 
 
                   <div className="flex justify-between items-center text-sm"> 
                     <div className="flex items-center gap-2 text-slate-600"> 
                       <Star size={14} className="text-amber-400 fill-amber-400" /> 
                       <span>Bonus Absensi</span> 
                     </div> 
                     <span className="font-medium text-emerald-600">+{formatCurrency(data.ringkasan.bonus_rincian.absensi)}</span> 
                   </div> 
                 </div> 
               </div> 
 
               <div className="pt-4 border-t border-dashed border-slate-200 flex justify-between font-bold text-slate-800"> 
                 <span className="text-xs uppercase tracking-tight">Total Pendapatan Kotor</span> 
                 <span>{formatCurrency(gajiPokok + totalBonus)}</span> 
               </div> 
             </div> 
 
             {/* Bagian Potongan & Ringkasan */} 
             <div className="p-8 space-y-6 bg-slate-50/30"> 
               <h3 className="text-sm font-bold text-rose-600 flex items-center gap-2 uppercase tracking-wider"> 
                 <TrendingDown size={16} /> Potongan 
               </h3> 
 
               <div className="space-y-4"> 
                 <div className="flex justify-between items-center text-sm"> 
                   <div className="text-slate-600"> 
                     <p className="font-medium">Potongan Kasbon</p> 
                     <p className="text-[10px] text-slate-400">Cicilan berjalan</p> 
                   </div> 
                   <span className="font-bold text-rose-600">-{formatCurrency(data.ringkasan.total_kasbon)}</span> 
                 </div> 
 
                 <div className="pt-4 border-t border-slate-200 space-y-3"> 
                   <p className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Kehadiran</p> 
                   <div className="flex justify-between text-xs"> 
                     <span className="text-slate-500 font-medium">Hadir: {data.ringkasan.present_count} Hari</span> 
                     <span className="text-slate-500 font-medium">Izin: {data.ringkasan.leave_count} Hari</span> 
                   </div> 
                 </div> 
               </div> 
 
               {/* Total Bersih */} 
               <div className="mt-8 p-6 bg-slate-900 rounded-2xl text-white flex justify-between items-center shadow-lg"> 
                 <div> 
                   <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Take Home Pay</p> 
                   <p className="text-[8px] text-slate-500 uppercase mt-0.5">Gaji Bersih Diterima</p> 
                 </div> 
                 <div className="text-2xl font-black text-indigo-400"> 
                   {formatCurrency(totalGaji)} 
                 </div> 
               </div> 
             </div> 
           </div> 
 
           {/* Footer Tanda Tangan */} 
           <div className="p-8 border-t border-slate-100 flex flex-col md:flex-row justify-between items-end gap-8"> 
             <div className="text-[10px] text-slate-400 max-w-sm space-y-2"> 
               <p className="flex items-start gap-1"> 
                 <CheckCircle2 size={12} className="mt-0.5 text-emerald-500 shrink-0" /> 
                 Slip gaji ini adalah rincian resmi pendapatan teknisi berdasarkan performa disiplin, tanggung jawab, dan kehadiran. 
               </p> 
             </div> 
             <div className="grid grid-cols-2 gap-12 text-center w-full md:w-auto"> 
               <div className="min-w-[120px]"> 
                 <p className="text-[10px] font-bold text-slate-400 uppercase mb-12">Penerima</p> 
                 <div className="border-t border-slate-200 pt-2"> 
                   <p className="text-xs font-bold text-slate-800">{data.user.name}</p> 
                 </div> 
               </div> 
               <div className="min-w-[120px]"> 
                 <p className="text-[10px] font-bold text-slate-400 uppercase mb-12">Admin Finance</p> 
                 <div className="border-t border-slate-200 pt-2"> 
                   <p className="text-xs font-bold text-slate-800">Siska Putri</p> 
                 </div> 
               </div> 
             </div> 
           </div> 
         </div> 
 
         <p className="text-center text-slate-400 text-[9px] pb-10 uppercase tracking-[0.2em]"> 
           E-Payslip • Generated by Management System 
         </p> 
       </div> 
 
       <style dangerouslySetInnerHTML={{ __html: ` 
         @media print { 
           body { background-color: white !important; padding: 0 !important; } 
           .min-h-screen { min-height: auto !important; padding: 0 !important; } 
           .max-w-5xl { max-width: 100% !important; margin: 0 !important; } 
           .shadow-xl, .shadow-lg { box-shadow: none !important; } 
           .rounded-2xl { border-radius: 0 !important; } 
           .bg-slate-100 { background-color: white !important; } 
           .print\\:hidden { display: none !important; } 
         } 
       `}} /> 
     </div> 
   ); 
 }; 
 
 export default Payslip;
