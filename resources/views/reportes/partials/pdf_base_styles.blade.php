<style>
    @page { margin: {{ ($format ?? 'a4') === 'a4' ? '18mm' : '6mm' }}; }
    body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: {{ ($format ?? 'a4') === 'a4' ? '11px' : '9px' }}; }
    .muted { color: #64748b; }
    .header { border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px; }
    .brand { display: flex; align-items: center; gap: 10px; }
    .brand h1 { font-size: {{ ($format ?? 'a4') === 'a4' ? '16px' : '12px' }}; margin: 0; }
    .logo { width: {{ ($format ?? 'a4') === 'a4' ? '90px' : '60px' }}; height: auto; }
    .section { margin-top: 14px; }
    .section h2 { font-size: {{ ($format ?? 'a4') === 'a4' ? '12px' : '10px' }}; margin: 0 0 6px 0; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border-bottom: 1px solid #eef2f7; padding: 6px 4px; text-align: left; vertical-align: top; }
    .table th { font-size: 10px; color: #475569; }
    .right { text-align: right; }
    .badge { display:inline-block; padding:2px 6px; border-radius: 999px; font-size: 9px; border:1px solid #e2e8f0; color:#0f172a; background:#fff; }
    .badge-success { background: #dcfce7; border-color:#86efac; color:#166534; }
    .badge-warn { background: #ffedd5; border-color:#fdba74; color:#9a3412; }
    .badge-danger { background: #fee2e2; border-color:#fca5a5; color:#991b1b; }
    .badge-info { background: #dbeafe; border-color:#93c5fd; color:#1e40af; }
    .footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 9px; color: #64748b; }
</style>
