@extends('layouts.dashboard')

@section('title', 'Product: ' . $product->name . ' - Admin')
@section('page-title', 'PRODUCT DETAILS')

@section('content')
{{-- Breadcrumbs & Back Bar --}}
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <a href="{{ route('admin.dashboard') }}" style="color: var(--text-secondary); text-decoration: none;">Admin Hub</a>
        <span>/</span>
        <a href="{{ route('product.index') }}" style="color: var(--text-secondary); text-decoration: none;">Products</a>
        <span>/</span>
        <span style="color: #ffffff;">#{{ $product->id }} {{ $product->name }}</span>
    </div>

    <div style="display: flex; gap: 8px;">
        <a href="{{ route('product.edit', $product->id) }}" class="cyber-btn cyber-btn-primary cyber-btn-sm">
            <i class="fa-solid fa-pen-to-square"></i> EDIT
        </a>
        <a href="{{ route('product.index') }}" class="cyber-btn cyber-btn-secondary cyber-btn-sm">
            <i class="fa-solid fa-arrow-left"></i> BACK
        </a>
    </div>
</div>

<div class="split-grid" style="align-items: start;">
    {{-- Left: Overview Spec --}}
    <div class="cyber-panel">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-circle-info"></i>
                SPECIFICATIONS
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                @if ($product->published)
                    <span class="badge-status b-ok"><span class="status-dot online"></span> PUBLISHED</span>
                @else
                    <span class="badge-status b-err"><span class="status-dot offline"></span> DRAFT</span>
                @endif

                @if ($product->active)
                    <span class="badge-status b-ok"><span class="status-dot online"></span> ACTIVE</span>
                @else
                    <span class="badge-status b-err"><span class="status-dot offline"></span> INACTIVE</span>
                @endif
            </div>
        </div>

        <table class="info-table">
            <tbody>
                <tr>
                    <th style="width: 140px;">DATABASE ID</th>
                    <td style="font-family: var(--font-mono); font-weight: 700; color: #ffffff;">#{{ $product->id }}</td>
                </tr>
                <tr>
                    <th>IDENTIFIER (PID)</th>
                    <td>
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                            <span style="font-family: var(--font-mono); font-weight: 700; color: #48cae4; background: rgba(72, 202, 228, 0.1); border: 1px solid rgba(72, 202, 228, 0.3); padding: 3px 10px; border-radius: var(--radius-sm); font-size: 0.85rem;">
                                {{ $product->pid ?: 'PID-' . sprintf('%04d', $product->id) }}
                            </span>
                            <button 
                                type="button" 
                                class="cyber-copy-btn" 
                                style="padding: 3px 8px; font-size: 0.72rem;" 
                                onclick="copyText('{{ $product->pid ?: 'PID-' . sprintf('%04d', $product->id) }}', this)" 
                                title="Copy PID"
                            >
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>NAME</th>
                    <td style="font-weight: 700; color: #ffffff;">{{ $product->name }}</td>
                </tr>
                <tr>
                    <th>PUBLISH STATUS</th>
                    <td>
                        @if ($product->published)
                            <span class="badge-status b-ok" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-circle-check"></i> Published (Publicly Listed & Enabled for License Validation)
                            </span>
                        @else
                            <span class="badge-status b-err" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-eye-slash"></i> Draft / Unpublished
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>STORE STATUS</th>
                    <td>
                        @if ($product->active)
                            <span class="badge-status b-ok" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-store"></i> Active (Purchasable in Store)
                            </span>
                        @else
                            <span class="badge-status b-err" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-ban"></i> Inactive
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>PRICE</th>
                    <td>
                        <span style="font-family: var(--font-mono); font-size: 1.1rem; font-weight: 800; color: #00ff66;">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>DESCRIPTION</th>
                    <td style="color: #d0d0d0; line-height: 1.5;">
                        <div class="ql-editor" style="padding: 0; min-height: unset;">
                            {!! $product->description ?? 'No description provided.' !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>CREATED AT</th>
                    <td style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-secondary);">
                        {{ $product->created_at ? $product->created_at->format('Y-m-d H:i:s T') : 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <th>LAST UPDATED</th>
                    <td style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-secondary);">
                        {{ $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s T') : 'N/A' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Right: Version Releases List --}}
    <div class="cyber-panel">
        <div class="cyber-panel-header">
            <div class="cyber-panel-title">
                <i class="fa-solid fa-code-branch"></i>
                AVAILABLE RELEASES ({{ is_array($product->contents) ? count($product->contents) : 0 }})
            </div>
        </div>

        @php $contents = is_array($product->contents) ? $product->contents : []; @endphp

        @if (empty($contents))
            <p style="color: var(--text-muted); padding: 20px; text-align: center;">No release packages configured.</p>
        @else
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach ($contents as $rel)
                    <div style="background: rgba(14, 11, 16, 0.7); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: var(--radius-sm); padding: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-file-zipper" style="color: var(--red-primary);"></i>
                                <strong style="font-family: var(--font-mono); color: #ffffff; font-size: 0.88rem;">{{ $rel['file'] ?? 'package.zip' }}</strong>
                            </div>
                            <span class="badge-status b-ok" style="font-size: 0.72rem;">
                                v{{ $rel['version'] ?? '1.0.0' }}
                            </span>
                        </div>

                        @if (!empty($rel['changelog']))
                            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 8px; line-height: 1.4;">
                                {{ $rel['changelog'] }}
                            </div>
                        @endif

                        @if (!empty($rel['md5sum']))
                            <div style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-muted); background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 2px;">
                                MD5: <span style="color: #48cae4;">{{ $rel['md5sum'] }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
