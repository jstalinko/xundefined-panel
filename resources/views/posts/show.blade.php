<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }} // XUNDEFINED NEWS</title>

    <!-- Google Fonts: Inter & JetBrains Mono & Orbitron -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700;800;900&family=Orbitron:wght@600;800;900&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Free CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Theme: @public/app.css -->
    <link rel="stylesheet" href="{{ asset('app.css') }}?ver={{ date('dmY') }}">

    <style>
        .public-news-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .public-news-nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(5, 5, 5, 0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--red-border);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7);
        }

        .public-news-nav-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .public-news-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .public-brand-logo {
            width: 38px;
            height: 38px;
            background: radial-gradient(circle, var(--red-primary) 0%, #1a0006 100%);
            border: 1px solid var(--red-primary);
            box-shadow: 0 0 15px var(--red-glow);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.1rem;
        }

        .public-brand-title {
            font-family: 'Orbitron', var(--font-mono);
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #ffffff;
        }

        .public-brand-title span {
            color: var(--red-primary);
        }

        .public-news-container {
            max-width: 900px;
            width: 100%;
            margin: 36px auto 60px;
            padding: 0 20px;
            flex: 1;
        }

        .public-article-card {
            background: rgba(14, 14, 14, 0.92);
            border: 1px solid var(--red-border);
            border-radius: var(--radius-md);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.8), 0 0 16px var(--red-glow-soft);
            padding: 36px 32px;
            position: relative;
        }

        .article-category-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: var(--font-mono);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            background: rgba(255, 23, 68, 0.12);
            border: 1px solid var(--red-primary);
            color: var(--red-primary);
        }

        .article-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .article-timestamp {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .article-title-heading {
            font-family: 'Orbitron', 'JetBrains Mono', var(--font-mono);
            font-size: clamp(1.6rem, 3.5vw, 2.4rem);
            font-weight: 800;
            color: #ffffff;
            line-height: 1.25;
            letter-spacing: 0.03em;
            margin-bottom: 24px;
        }

        .article-divider {
            height: 1px;
            background: linear-gradient(90deg, var(--red-primary) 0%, rgba(255, 23, 68, 0.2) 60%, transparent 100%);
            margin: 24px 0 32px;
            box-shadow: 0 0 10px var(--red-glow);
        }

        .article-body-content {
            font-family: var(--font-sans);
            font-size: 1.05rem;
            line-height: 1.8;
            color: #e5e5e5;
            white-space: pre-line;
            word-break: break-word;
        }

        .article-body-content p {
            margin-bottom: 1.2em;
        }

        .article-actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .recent-news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-top: 20px;
        }

        .recent-news-card {
            background: rgba(14, 14, 14, 0.8);
            border: 1px solid rgba(255, 23, 68, 0.15);
            border-radius: var(--radius-sm);
            padding: 18px 20px;
            text-decoration: none;
            color: inherit;
            transition: all var(--transition-fast);
            display: block;
        }

        .recent-news-card:hover {
            border-color: var(--red-primary);
            box-shadow: 0 0 16px var(--red-glow-soft);
            transform: translateY(-2px);
        }

        .public-footer {
            border-top: 1px solid rgba(255, 23, 68, 0.2);
            padding: 24px 20px;
            text-align: center;
            font-family: var(--font-mono);
            font-size: 0.78rem;
            color: var(--text-muted);
            background: rgba(5, 5, 5, 0.95);
        }
    </style>
</head>
<body>
    {{-- Cyber Background Elements from app.css --}}
    <div class="cyber-grid"></div>
    <div class="scanlines"></div>

    <div class="public-news-wrapper">
        {{-- Navigation Header --}}
        <header class="public-news-nav">
            <div class="public-news-nav-inner">
                <a href="/" class="public-news-brand">
                    <div class="public-brand-logo">
                        <i class="fa-solid fa-terminal"></i>
                    </div>
                    <div class="public-brand-title">
                        XUNDEFINED <span>// INTEL</span>
                    </div>
                </a>

                <div style="display: flex; gap: 12px; align-items: center;">
                    <a href="https://t.me/xingzhengx" target="_blank" rel="noopener noreferrer" class="cyber-btn cyber-btn-secondary cyber-btn-sm" style="text-decoration: none;">
                        <i class="fa-brands fa-telegram" style="color: #48cae4;"></i>
                        <span>TELEGRAM</span>
                    </a>
                    <a href="/" class="cyber-btn cyber-btn-primary cyber-btn-sm" style="text-decoration: none;">
                        <i class="fa-solid fa-house"></i>
                        <span>HOME</span>
                    </a>
                </div>
            </div>
        </header>

        {{-- Main Article Container --}}
        <main class="public-news-container">
            <div style="margin-bottom: 20px;">
                <a href="/" style="color: var(--text-secondary); text-decoration: none; font-family: var(--font-mono); font-size: 0.82rem; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>BACK TO MAIN TERMINAL</span>
                </a>
            </div>

            <article class="public-article-card">
                <div class="cyber-corner top-left"></div>
                <div class="cyber-corner top-right"></div>
                <div class="cyber-corner bottom-left"></div>
                <div class="cyber-corner bottom-right"></div>

                {{-- Meta row --}}
                <div class="article-meta-row">
                    <span class="article-category-badge">
                        <i class="fa-solid fa-tag"></i>
                        {{ $post->category ?? 'ANNOUNCEMENT' }}
                    </span>

                    <span class="article-timestamp">
                        <i class="fa-regular fa-clock"></i>
                        {{ $post->created_at ? $post->created_at->format('F d, Y · H:i') : 'LIVE' }}
                    </span>
                </div>

                {{-- Title --}}
                <h1 class="article-title-heading">
                    {{ $post->title }}
                </h1>

                <div class="article-divider"></div>

                {{-- Featured Image if provided --}}
                @if (!empty($post->image) && $post->image !== '/no-image.svg')
                    <div style="margin-bottom: 28px; border: 1px solid var(--red-border); border-radius: var(--radius-sm); overflow: hidden; max-height: 420px;">
                        <img src="{{ $post->image }}" alt="{{ $post->title }}" style="width: 100%; height: auto; display: block; object-fit: cover;">
                    </div>
                @endif

                {{-- Content Body --}}
                <div class="article-body-content">
                    {!! nl2br(e($post->content)) !!}
                </div>

                {{-- Article Actions Footer --}}
                <div class="article-actions-bar">
                    <div style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                        <span class="status-dot online"></span>
                        <span>OFFICIAL XUNDEFINED INTEL DISPATCH</span>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <a href="https://t.me/xingzhengx" target="_blank" rel="noopener noreferrer" class="cyber-btn cyber-btn-secondary cyber-btn-sm" style="text-decoration: none;">
                            <i class="fa-brands fa-telegram" style="color: #48cae4;"></i>
                            <span>CONTACT SUPPORT</span>
                        </a>
                        <a href="/" class="cyber-btn cyber-btn-primary cyber-btn-sm" style="text-decoration: none;">
                            <i class="fa-solid fa-solar-panel"></i>
                            <span>STORE CATALOG</span>
                        </a>
                    </div>
                </div>
            </article>

            {{-- Recent Posts List --}}
            @if(isset($recentPosts) && $recentPosts->count() > 0)
                <div style="margin-top: 48px;">
                    <div style="font-family: 'Orbitron', var(--font-mono); font-size: 1rem; font-weight: 700; color: #ffffff; letter-spacing: 0.06em; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-newspaper" style="color: var(--red-primary);"></i>
                        <span>MORE DISPATCHES & UPDATES</span>
                    </div>

                    <div class="recent-news-grid">
                        @foreach ($recentPosts as $otherPost)
                            <a href="{{ url('/news/' . $otherPost->slug) }}" class="recent-news-card">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="font-family: var(--font-mono); font-size: 0.68rem; color: var(--red-primary); text-transform: uppercase;">
                                        [{{ $otherPost->category }}]
                                    </span>
                                    <span style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-muted);">
                                        {{ $otherPost->created_at ? $otherPost->created_at->format('M d') : '' }}
                                    </span>
                                </div>
                                <div style="font-family: 'Inter', sans-serif; font-size: 0.92rem; font-weight: 700; color: #ffffff; line-height: 1.4; margin-bottom: 6px;">
                                    {{ Str::limit($otherPost->title, 60) }}
                                </div>
                                <div style="font-family: var(--font-mono); font-size: 0.72rem; color: #48cae4;">
                                    Read Article &rarr;
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </main>

        {{-- Footer --}}
        <footer class="public-footer">
            <p>XUNDEFINED LABS &copy; {{ date('Y') }} // AUTOMATED REPOSITORY & SECURE LICENSING</p>
        </footer>
    </div>
</body>
</html>
