<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="/Hotel-Restaurant/assets/Css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>

<style>
    /* Button Styles */
    .btn {
        display: inline-block;
        padding: 10px 24px;
        color: #000000;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.18s, box-shadow 0.18s;
        margin-top: 14px;
    }

    .btn:hover,
    .btn:focus {
        color: #000000;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(67, 97, 238, 0.1);
    }

    .category-list {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-bottom: 32px;
        justify-content: center;
    }

    .category-item {
        display: flex;
        align-items: center;
        background: #f7f8fd;
        border-radius: 8px;
        padding: 10px 18px;
        font-weight: 600;
        color: #3f37c9;
        font-size: 1.08rem;
        box-shadow: 0 2px 8px rgba(67, 97, 238, 0.08);
        transition: background 0.18s;
    }

    .category-item i {
        margin-right: 10px;
        font-size: 1.3rem;
    }

    /* Active state for filter buttons */
    .filter-btn.active {
        background: #3f37c9;
        color: #ffffff;
    }

    /* Loading Overlay Styles */
    .loading-overlay {
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #232526 0%, #8e44ad 100%);
        transition: opacity 0.7s cubic-bezier(.4, 0, .2, 1);
        opacity: 1;
    }

    .loading-overlay.hide {
        opacity: 0;
        pointer-events: none;
    }

    .loading-container {
        text-align: center;
        color: #fff;
    }

    .logo {
        font-size: 2.8rem;
        font-weight: 900;
        letter-spacing: 3px;
        margin-bottom: 24px;
        font-family: 'Playfair Display', serif;
        text-shadow: 0 4px 24px #8e44ad, 0 1px 0 #fff;
    }

    .spinner {
        margin: 0 auto 32px auto;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .spinner div {
        width: 80px;
        height: 80px;
        border: 8px solid rgba(255, 255, 255, 0.18);
        border-top: 8px solid #fff;
        border-radius: 50%;
        animation: spin 1.1s linear infinite;
        box-shadow: 0 0 32px #8e44ad, 0 0 8px #fff;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .loading-text {
        font-size: 1.25rem;
        letter-spacing: 1.2px;
        margin-top: 8px;
        opacity: 0.92;
    }

    /* ========== Menu Styles ========== */
    .menu-wrapper {
        width: 100%;
        overflow: hidden;
        padding: 1rem 0;
        position: relative;
        z-index: 0;
        margin: 2rem 0 2.5rem 0;
    }

    .menu-container {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 2.5rem;
        width: 100%;
        justify-content: flex-start;
        animation: none;
        will-change: auto;
        overflow: visible;
    }

    .menu-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        border-radius: 12px;
        padding: 0.9rem 1.6rem;
        min-width: 120px;
        cursor: pointer;
        transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
    }

    .menu-item img {
        width: 80px;
        height: 80px;
        margin-bottom: 0.6rem;
        object-fit: cover;
        border-radius: 8px;
        transition: width 0.2s, height 0.2s;
    }

    .menu-item p {
        margin: 0;
        font-size: 1.08rem;
        color: #e67e22;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .menu-wrapper .arrow {
        display: none;
    }
</style>

<body>
    <!-- Enhanced Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay"
        style="position:fixed;z-index:9999;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#232526 0%,#8e44ad 100%);transition:opacity 0.7s cubic-bezier(.4,0,.2,1);opacity:1;">
        <div class="loading-container" style="text-align:center;color:#fff;">
            <div class="spinner" style="margin:0 auto 32px auto;width:80px;height:80px;display:flex;align-items:center;justify-content:center;">
                <div
                    style="width:80px;height:80px;border:8px solid rgba(255,255,255,0.18);border-top:8px solid #fff;border-radius:50%;animation:spin 1.1s linear infinite;box-shadow:0 0 32px #8e44ad,0 0 8px #fff;">
                </div>
            </div>
            <div class="loading-text"
                style="font-size:1.25rem;letter-spacing:1.2px;margin-top:8px;opacity:0.92;">
                Welcome to RoyalNest. Preparing your royal experience...
            </div>
        </div>
        <style>
            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const overlay = document.getElementById('loadingOverlay');
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
                overlay.addEventListener('transitionend', function() {
                    overlay.parentNode.removeChild(overlay);
                }, {
                    once: true
                });
            }, 1800);
        });
    </script>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/include/navbar.php'; ?>
</body>

</html>