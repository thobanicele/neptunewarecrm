@php
    $tenant = $invite->tenant;
@endphp

<div style="font-family: Arial, sans-serif; line-height:1.5;">
    <h2 style="margin:0 0 10px 0;">You're invited to join {{ $tenant->name }}</h2>

    <p style="margin:0 0 12px 0;">
        You’ve been invited to join the workspace <b>{{ $tenant->name }}</b> on NeptuneWare CRM.
    </p>

    <p style="margin:0 0 12px 0;">
        <b>Your role:</b> {{ str_replace('_', ' ', ucwords($invite->role)) }}
    </p>

    <p style="margin:0 0 18px 0;">
        Click below to accept the invitation:
    </p>

    <p style="margin:0 0 18px 0;">
        <a href="{{ $acceptUrl }}"
            style="display:inline-block;padding:10px 14px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;">
            Accept invitation
        </a>
    </p>

    <p style="color:#666;margin:0 0 6px 0;">
        This invite expires on <b>{{ optional($invite->expires_at)->format('Y-m-d H:i') }}</b>.
    </p>

    <p style="color:#666;margin:0;">
        If you didn't expect this email, you can ignore it.
    </p>
</div>
