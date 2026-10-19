@pushOnce('styles')
<style>
    /* Phase 1: Meta-style rail, labels, ticks, service window, composer tools */
    .ui-rail{display:none;flex-direction:column;gap:2px;padding:12px 8px;border-inline-end:1px solid var(--ui-line,#e7eaf2);background:var(--ui-soft,#f6f7fb);overflow-y:auto;min-width:0}
    .ui-rail-head{margin:12px 8px 4px;font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:var(--ui-muted,#74809a)}
    .ui-rail-head:first-child{margin-top:0}
    .ui-rail-item{display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:8px;font-size:13px;color:var(--ui-ink,#101c38);text-decoration:none}
    .ui-rail-item:hover{background:rgba(68,76,244,.07)}
    .ui-rail-item[aria-current="page"]{background:#e8e9fe;color:var(--ui-accent,#444cf4);font-weight:600}
    .ui-rail-item .ui-channel{width:20px;height:20px;flex:0 0 20px}
    .ui-rail-icon{flex:0 0 16px}
    .ui-rail-label{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-rail-count{font-size:11px;padding:0 6px;border-radius:999px;background:#fff;border:1px solid var(--ui-line,#e7eaf2);color:var(--ui-muted,#74809a)}
    @media(min-width:1024px){
        .ui-inbox .ui-columns.ui-has-rail{grid-template-columns:184px 300px minmax(0,1fr)}
        .ui-inbox .ui-columns.ui-has-rail .ui-rail{display:flex}
    }
    .ui-label-dot{width:10px;height:10px;border-radius:999px;flex:0 0 10px;background:hsl(var(--ui-label-hue,230) 70% 55%)}
    .ui-labels{display:flex;flex-wrap:wrap;gap:4px;margin-top:2px}
    .ui-label{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;line-height:16px;padding:0 6px;border-radius:999px;background:hsl(var(--ui-label-hue,230) 80% 94%);color:hsl(var(--ui-label-hue,230) 60% 32%)}
    .ui-label button{border:0;background:none;color:inherit;cursor:pointer;font-size:12px;line-height:1;padding:0}
    .ui-label-editor{display:flex;flex-direction:column;gap:8px}
    .ui-label-editor input{border:1px solid var(--ui-line,#e7eaf2);border-radius:8px;padding:6px 8px;font-size:13px}
    .ui-avatar-img{width:100%;height:100%;object-fit:cover;border-radius:inherit}
    .ui-unread-dot{width:9px;height:9px;border-radius:999px;background:var(--ui-accent,#444cf4);flex:0 0 9px}
    /* WhatsApp-style ticks */
    .ui-tick{display:inline-flex;align-items:center;color:#8696a0}
    .ui-tick svg{display:block}
    .ui-tick-read{color:#34b7f1}
    .ui-tick-failed{color:#e5484d;font-weight:700}
    .ui-tick-pending{color:#8696a0}
    /* Header quick actions */
    .ui-flag-btn{display:inline-flex;align-items:center;gap:4px;height:30px;padding:0 10px;border-radius:8px;border:1px solid var(--ui-line,#e7eaf2);background:#fff;font-size:12px;cursor:pointer;color:var(--ui-ink,#101c38)}
    .ui-flag-btn:hover{border-color:var(--ui-accent,#444cf4)}
    .ui-flag-btn.is-on{background:#e8e9fe;border-color:var(--ui-accent,#444cf4);color:var(--ui-accent,#444cf4)}
    .ui-flag-btn.is-spam.is-on{background:#fde8e8;border-color:#e5484d;color:#b42318}
    /* 24h service window */
    .ui-window{display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:8px;font-size:12px;margin-bottom:6px}
    .ui-window-open{background:#e7f7ee;color:#11733b}
    .ui-window-closed{background:#fff4e5;color:#8a4b00}
    .ui-window button{margin-inline-start:auto}
    .ui-composer.is-window-closed textarea{background:#f4f4f6;cursor:not-allowed}
    /* Composer tools */
    .ui-composer-tools{display:flex;align-items:center;gap:4px;position:relative}
    .ui-tool-btn{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:0;background:none;cursor:pointer;color:var(--ui-muted,#74809a);font-size:18px}
    .ui-tool-btn:hover{background:var(--ui-soft,#f6f7fb);color:var(--ui-ink,#101c38)}
    .ui-tool-btn:disabled{opacity:.4;cursor:not-allowed}
    .ui-popover{position:absolute;bottom:42px;left:0;z-index:30;background:#fff;border:1px solid var(--ui-line,#e7eaf2);border-radius:10px;box-shadow:0 12px 32px rgba(16,28,56,.14);padding:8px}
    .ui-emoji-grid{display:grid;grid-template-columns:repeat(8,30px);gap:2px}
    .ui-emoji-grid button{border:0;background:none;font-size:20px;line-height:30px;border-radius:6px;cursor:pointer}
    .ui-emoji-grid button:hover{background:var(--ui-soft,#f6f7fb)}
    .ui-slash{min-width:280px;max-width:420px;max-height:240px;overflow-y:auto}
    .ui-slash button{display:block;width:100%;text-align:left;border:0;background:none;padding:6px 8px;border-radius:6px;cursor:pointer;font-size:13px}
    .ui-slash button.is-active,.ui-slash button:hover{background:#e8e9fe}
    .ui-slash small{display:block;color:var(--ui-muted,#74809a);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ui-attach-preview{display:flex;align-items:center;gap:8px;font-size:12px;padding:6px 8px;border:1px dashed var(--ui-line,#e7eaf2);border-radius:8px;margin-bottom:6px}
    .ui-attach-preview[hidden],.ui-popover[hidden],.ui-emoji-grid[hidden]{display:none!important}
    .ui-attach-preview button{margin-inline-start:auto;border:0;background:none;cursor:pointer}
    /* Bubbles: attachments */
    .ui-msg-media{display:block;max-width:260px;max-height:260px;border-radius:8px;margin-bottom:4px;object-fit:cover}
    .ui-msg-file{display:flex;align-items:center;gap:8px;padding:8px;border-radius:8px;background:rgba(0,0,0,.05);margin-bottom:4px;font-size:12px;text-decoration:none;color:inherit}
    .ui-msg-audio{display:block;max-width:260px;margin-bottom:4px}
    /* Quick replies manager */
    .ui-qr-dialog{border:0;border-radius:14px;padding:0;width:min(560px,94vw);box-shadow:0 24px 64px rgba(16,28,56,.25)}
    .ui-qr-dialog::backdrop{background:rgba(16,28,56,.35)}
    .ui-qr-body{padding:16px;display:flex;flex-direction:column;gap:10px;max-height:70vh;overflow-y:auto}
    .ui-qr-row{display:flex;gap:8px;align-items:flex-start;padding:8px;border:1px solid var(--ui-line,#e7eaf2);border-radius:8px}
    .ui-qr-row div{flex:1;min-width:0}
    .ui-qr-row p{margin:2px 0 0;font-size:12px;color:var(--ui-muted,#74809a);white-space:pre-wrap}
    .ui-qr-form{display:grid;gap:8px}
    .ui-qr-form input,.ui-qr-form textarea{border:1px solid var(--ui-line,#e7eaf2);border-radius:8px;padding:6px 8px;font-size:13px;width:100%}
    /* Number history */
    .ui-pill-oldnum{background:#f1f0ea;color:#6b5d2f;border:1px dashed #cdbf8f}
    .ui-prior{margin:0 0 12px;padding:8px 10px;border:1px dashed var(--ui-line,#e7eaf2);border-radius:10px;background:#fafafa;font-size:12px}
    .ui-prior summary{cursor:pointer;color:var(--ui-muted,#74809a)}
    .ui-prior summary a{margin-inline-start:6px;color:var(--ui-accent,#444cf4)}
    .ui-message-prior{opacity:.75}
    .ui-numhist{margin-top:24px}
    .ui-numhist table{width:100%;border-collapse:collapse;font-size:13px}
    .ui-numhist th,.ui-numhist td{padding:8px 10px;border-bottom:1px solid var(--ui-line,#e7eaf2);text-align:left}
    .ui-numhist .is-current{color:#11733b;font-weight:600}
</style>
@endPushOnce
