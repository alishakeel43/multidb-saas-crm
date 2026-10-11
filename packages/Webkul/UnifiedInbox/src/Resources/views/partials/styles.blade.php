@pushOnce('styles')
<style>
    .ui-workspace {
        --ui-bg:#fff;
        --ui-soft:#f6f7fb;
        --ui-line:#e7eaf2;
        --ui-ink:#101c38;
        --ui-muted:#74809a;
        --ui-accent:#444cf4;
        color:var(--ui-ink);
        background:var(--ui-bg);
        border:1px solid var(--ui-line);
        border-radius:16px;
        overflow:hidden;
        min-width:0;
    }
    .ui-workspace * {
        box-sizing:border-box;
    }
    .ui-workspace h1 {
        font-size:28px;
        font-weight:700;
        letter-spacing:-.8px;
    }
    .ui-workspace h2 {
        font-size:16px;
        font-weight:600;
    }
    .ui-workspace p {
        overflow-wrap:anywhere;
    }
    .ui-header {
        padding:24px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        flex-wrap:wrap;
    }
    .ui-actions {
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .ui-workspace .primary-button {
        background:var(--ui-accent);
        border-color:var(--ui-accent);
        color:white;
        border-radius:9px;
    }
    .ui-workspace .secondary-button {
        background:var(--ui-bg);
        border:1px solid var(--ui-line);
        color:var(--ui-ink);
        border-radius:9px;
    }
    .ui-workspace button,.ui-workspace a,.ui-workspace summary {
        transition:background .15s,box-shadow .15s;
    }
    .ui-workspace button:hover:not(:disabled),.ui-workspace .secondary-button:hover {
        box-shadow:0 3px 12px #444cf41a;
    }
    .ui-workspace :focus-visible {
        outline:3px solid #9499ff;
        outline-offset:3px;
    }
    .ui-workspace button:disabled {
        opacity:.45;
        cursor:not-allowed;
    }
    .ui-muted {
        color:var(--ui-muted);
        font-size:13px;
    }
    .ui-filters {
        padding:0 24px 20px;
        display:flex;
        gap:12px;
        align-items:center;
        flex-wrap:wrap;
    }
    .ui-workspace input:not([type=checkbox]):not([type=hidden]),.ui-workspace select,.ui-workspace textarea {
        border:1px solid var(--ui-line);
        background:var(--ui-soft);
        color:var(--ui-ink);
        border-radius:9px;
        padding:10px 12px;
        max-width:100%;
        min-width:0;
    }
    .ui-search {
        flex:1;
        min-width:180px;
    }
    .ui-search input {
        width:100%;
    }
    .ui-tabs {
        display:flex;
        gap:10px;
        padding:0 24px 20px;
        overflow-x:auto;
        border-bottom:1px solid var(--ui-line);
    }
    .ui-tab {
        display:flex;
        align-items:center;
        gap:9px;
        white-space:nowrap;
        padding:10px 14px;
        border:1px solid var(--ui-line);
        border-radius:12px;
        background:var(--ui-soft);
        font-size:13px;
        font-weight:600;
    }
    .ui-tab[aria-current=page] {
        color:var(--ui-accent);
        background:#eeefff;
        border-color:#bfc3ff;
    }
    .ui-channel {
        display:inline-grid;
        place-items:center;
        width:28px;
        height:28px;
        border-radius:9px;
        background:#e9edff;
        color:#4251ef;
        font-size:12px;
        font-weight:800;
        flex-shrink:0;
    }
    .ui-channel-whatsapp {
        background:#dff7e9;
        color:#139c54;
    }
    .ui-channel-messenger {
        background:#e4efff;
        color:#087cff;
    }
    .ui-channel-instagram {
        background:linear-gradient(135deg,#f9e2ff,#ffe4db);
        color:#cb3580;
    }
    .ui-columns {
        display:grid;
        grid-template-columns:300px minmax(0,1fr);
        min-height:580px;
    }
    .ui-list {
        border-inline-end:1px solid var(--ui-line);
        min-width:0;
    }
    .ui-list-heading {
        padding:20px;
        display:flex;
        align-items:center;
        justify-content:space-between;
    }
    .ui-conversations {
        max-height:680px;
        overflow:auto;
        padding:0 10px 12px;
    }
    .ui-conversation {
        display:flex;
        gap:12px;
        padding:16px 10px;
        border-radius:10px;
        border-bottom:1px solid var(--ui-line);
        color:var(--ui-ink);
    }
    .ui-conversation:hover,.ui-conversation[aria-current=page] {
        background:#eeefff;
    }
    .ui-avatar {
        display:grid;
        place-items:center;
        width:44px;
        height:44px;
        border-radius:50%;
        background:linear-gradient(135deg,#dfe5ff,#f0e5fa);
        color:#505590;
        font-weight:700;
        flex-shrink:0;
    }
    .ui-conversation-copy {
        flex:1;
        min-width:0;
    }
    .ui-conversation-copy strong,.ui-preview {
        display:block;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .ui-preview {
        color:var(--ui-muted);
        font-size:13px;
        margin-top:6px;
    }
    .ui-count {
        background:var(--ui-accent);
        color:white;
        border-radius:20px;
        padding:2px 6px;
        font-size:11px;
    }
    .ui-empty {
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
        gap:16px;
        padding:40px;
        min-height:400px;
        background:radial-gradient(ellipse at center,#f3f3ff,transparent 65%);
    }
    .ui-empty .ui-avatar {
        width:76px;
        height:76px;
        font-size:30px;
    }
    .ui-pagination {
        padding:12px;
        overflow:auto;
    }
    .ui-chat-layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 320px;
        min-width:0;
    }
    .ui-chat {
        min-width:0;
        display:flex;
        flex-direction:column;
    }
    .ui-chat-header {
        padding:20px;
        border-bottom:1px solid var(--ui-line);
        display:flex;
        gap:12px;
        align-items:center;
        flex-wrap:wrap;
    }
    .ui-chat-header h1 {
        font-size:20px;
    }
    .ui-history {
        padding:20px;
        display:flex;
        flex-direction:column;
        gap:18px;
        max-height:570px;
        overflow:auto;
        background:linear-gradient(#fff,var(--ui-soft));
    }
    .ui-message {
        align-self:flex-start;
        max-width:88%;
        padding:13px 16px;
        border-radius:14px 14px 14px 3px;
        background:#eceef4;
        overflow-wrap:anywhere;
    }
    .ui-message-outgoing {
        align-self:flex-end;
        background:var(--ui-accent);
        color:white;
        border-radius:14px 14px 3px 14px;
    }
    .ui-message-meta {
        font-size:11px;
        opacity:.75;
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-bottom:7px;
    }
    .ui-message-outgoing a {
        color:white;
    }
    .ui-composer {
        padding:18px;
        border-top:1px solid var(--ui-line);
        margin-top:auto;
    }
    .ui-composer textarea {
        width:100%;
        min-height:85px;
    }
    .ui-profile {
        padding:16px;
        border-inline-start:1px solid var(--ui-line);
        min-width:0;
        display:flex;
        flex-direction:column;
        gap:14px;
        max-height:760px;
        background:linear-gradient(180deg,var(--ui-bg),var(--ui-soft));
    }
    .ui-profile-head {
        display:flex;
        gap:12px;
        align-items:center;
        padding-bottom:4px;
    }
    .ui-profile-head-copy {
        min-width:0;
        flex:1;
    }
    .ui-profile-head-copy h2 {
        font-size:16px;
        line-height:1.3;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .ui-side-tabs-root {
        display:flex;
        flex-direction:column;
        gap:14px;
        min-height:0;
        flex:1;
    }
    .ui-side-radio {
        position:absolute;
        width:1px;
        height:1px;
        margin:-1px;
        padding:0;
        overflow:hidden;
        clip:rect(0,0,0,0);
        white-space:nowrap;
        border:0;
        opacity:0;
        pointer-events:none;
    }
    .ui-side-tablist {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:6px;
    }
    .ui-side-tab {
        appearance:none;
        display:flex;
        align-items:center;
        justify-content:center;
        border:1px solid var(--ui-line);
        background:var(--ui-bg);
        color:var(--ui-muted);
        border-radius:10px;
        padding:8px 6px;
        font-size:12px;
        font-weight:700;
        cursor:pointer;
        min-width:0;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        text-align:center;
        line-height:1.2;
        user-select:none;
    }
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-tab[data-side-tab=overview],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-tab[data-side-tab=crm],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-tab[data-side-tab=work],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=assist]:checked) .ui-side-tab[data-side-tab=assist] {
        color:var(--ui-accent);
        background:#eeefff;
        border-color:#bfc3ff;
        box-shadow:0 2px 8px #444cf41a;
    }
    .ui-side-panels {
        min-height:0;
        flex:1;
        overflow:auto;
        padding-right:2px;
    }
    .ui-side-panel {
        display:none;
    }
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-panel[data-side-panel=overview],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-panel[data-side-panel=crm],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-panel[data-side-panel=work],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=assist]:checked) .ui-side-panel[data-side-panel=assist] {
        display:block;
    }
    .ui-side-panel section {
        padding:14px 0;
        border-bottom:1px solid var(--ui-line);
    }
    .ui-side-panel section:first-child {
        padding-top:2px;
    }
    .ui-side-panel section:last-child {
        border-bottom:0;
        padding-bottom:4px;
    }
    .ui-side-item {
        padding:10px 0;
        border-bottom:1px solid var(--ui-line);
    }
    .ui-side-item:last-child {
        border-bottom:0;
    }
    .ui-side-suggestion {
        margin-bottom:12px;
    }
    .ui-side-check {
        display:flex!important;
        align-items:center;
        gap:8px;
        flex:unset!important;
        width:100%;
        color:var(--ui-ink)!important;
        font-size:13px!important;
    }
    .ui-profile label:not(.ui-side-tab):not(.ui-side-check) {
        display:block;
        font-size:12px;
        color:var(--ui-muted);
        flex:1 1 140px;
        min-width:0;
    }
    .ui-profile label:has(textarea) {
        flex:1 1 100%;
        width:100%;
    }
    .ui-profile input:not([type=checkbox]):not([type=hidden]):not(.ui-side-radio),
    .ui-profile select,
    .ui-profile textarea {
        width:100%;
        margin-top:6px;
        display:block;
        box-sizing:border-box;
    }
    .ui-profile textarea {
        min-height:84px;
        resize:vertical;
        line-height:1.4;
    }
    .ui-profile form {
        display:flex;
        flex-wrap:wrap;
        gap:12px;
        align-items:end;
        margin-top:12px;
    }
    .ui-side-panel[data-side-panel=assist] form,
    .ui-profile form.ui-side-stack {
        flex-direction:column;
        flex-wrap:nowrap;
        align-items:stretch;
    }
    .ui-side-panel[data-side-panel=assist] form > label:not(.ui-side-check),
    .ui-profile form.ui-side-stack > label:not(.ui-side-check) {
        flex:none;
        width:100%;
    }
    .ui-side-panel[data-side-panel=assist] form > .primary-button,
    .ui-side-panel[data-side-panel=assist] form > .secondary-button,
    .ui-profile form.ui-side-stack > .primary-button,
    .ui-profile form.ui-side-stack > .secondary-button {
        align-self:flex-start;
        width:auto;
    }
    .dark .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-tab[data-side-tab=overview],
    .dark .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-tab[data-side-tab=crm],
    .dark .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-tab[data-side-tab=work],
    .dark .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=assist]:checked) .ui-side-tab[data-side-tab=assist] {
        background:#283254;
        color:#c7cbff;
        border-color:#3d4d74;
    }
    .ui-notice {
        padding:12px 18px;
        background:var(--ui-soft);
        font-size:13px;
        border-bottom:1px solid var(--ui-line);
    }
    .ui-channel-content {
        padding:0 24px 24px;
    }
    .ui-channel-cards {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:18px;
        margin:24px 0;
    }
    .ui-card {
        border:1px solid var(--ui-line);
        border-radius:14px;
        padding:22px;
        display:flex;
        flex-direction:column;
        gap:14px;
        background:var(--ui-bg);
    }
    .ui-card> .ui-channel {
        width:44px;
        height:44px;
        font-size:18px;
    }
    .ui-card p {
        color:var(--ui-muted);
        line-height:1.7;
        font-size:13px;
    }
    .ui-card>form,.ui-card>button {
        margin-top:auto;
    }
    .ui-card button {
        justify-content:center;
    }
    .ui-website {
        background:var(--ui-soft);
        margin-bottom:28px;
    }
    .ui-website form {
        display:flex;
        gap:16px;
        flex-wrap:wrap;
        align-items:end;
    }
    .ui-website label {
        flex:1;
        min-width:180px;
        font-size:13px;
    }
    .ui-website input {
        display:block;
        width:100%;
        margin-top:7px;
    }
    .ui-connection {
        margin-top:14px;
    }
    .ui-connection textarea {
        font-family:monospace;
        font-size:12px;
        min-height:80px;
    }
    .ui-setup {
        margin-top:24px;
        padding:18px;
        border:1px solid var(--ui-line);
        border-radius:12px;
        overflow-wrap:anywhere;
    }
    .ui-setup summary {
        cursor:pointer;
        font-weight:600;
    }
    .ui-setup p {
        margin-top:12px;
        font-size:13px;
        color:var(--ui-muted);
    }
    .dark .ui-workspace {
        --ui-bg:#111b2e;
        --ui-soft:#18243a;
        --ui-line:#2c3951;
        --ui-ink:#edf1fa;
        --ui-muted:#a3afc6;
    }
    .dark .ui-history {
        background:var(--ui-bg);
    }
    .dark .ui-message:not(.ui-message-outgoing) {
        background:#25314a;
    }
    .dark .ui-conversation:hover,.dark .ui-conversation[aria-current=page],.dark .ui-tab[aria-current=page] {
        background:#283254;
        color:#c7cbff;
    }
    @media(min-width:1600px) {
        .ui-columns {
            grid-template-columns:330px minmax(0,1fr);
        }
        .ui-chat-layout {
            grid-template-columns:minmax(0,1fr) 340px;
        }
    }
    @media(max-width:1250px) {
        .ui-chat-layout {
            grid-template-columns:minmax(0,1fr);
        }
        .ui-profile {
            border-inline-start:0;
            border-top:1px solid var(--ui-line);
            max-height:none;
        }
        .ui-side-panel section {
            padding:12px 0;
        }
        .ui-channel-cards {
            gap:12px;
        }
        .ui-card {
            padding:18px;
        }
    }
    @media(max-width:767px) {
        .ui-header {
            padding:18px;
        }
        .ui-workspace h1 {
            font-size:24px;
        }
        .ui-filters {
            padding:0 18px 16px;
            gap:10px;
        }
        .ui-search {
            flex-basis:100%;
        }
        .ui-tabs {
            padding:0 18px 16px;
        }
        .ui-columns {
            grid-template-columns:minmax(0,1fr);
            min-height:0;
        }
        .ui-list {
            border-inline-end:0;
        }
        .ui-detail .ui-list {
            display:none;
        }
        .ui-index .ui-empty {
            display:none;
        }
        .ui-conversations {
            max-height:none;
        }
        .ui-channel-cards {
            grid-template-columns:1fr;
        }
        .ui-channel-content {
            padding:0 18px 18px;
        }
        .ui-chat-header {
            padding:16px;
        }
        .ui-history {
            padding:14px;
            max-height:60vh;
            min-height:260px;
        }
        .ui-message {
            max-width:94%;
        }
        .ui-composer {
            padding:14px;
        }
        .ui-website label {
            min-width:100%;
        }
        .ui-workspace {
            border-radius:12px;
        }
        .ui-profile {
            padding:16px;
        }
    }
    @media(prefers-reduced-motion:reduce) {
        .ui-workspace * {
            transition:none!important;
            scroll-behavior:auto!important;
        }
    }
</style>
@endPushOnce
