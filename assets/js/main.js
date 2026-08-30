/**
 * LUXE BITES - Main JavaScript
 * 3D scroll animation, cart management, AJAX handlers
 */

$(document).ready(function() {
    // ============================================
    // NAVBAR SCROLL EFFECT
    // ============================================
    $(window).scroll(function() {
        if ($(this).scrollTop() > 50) {
            $('.navbar-luxe').addClass('scrolled');
        } else {
            $('.navbar-luxe').removeClass('scrolled');
        }
    });

    // ============================================
    // CINEMATIC SCROLL-DRIVEN HERO ANIMATION
    // CRITICAL: Single DOM element transforms
    // ============================================
    const heroImage = document.getElementById('hero-dish');
    const heroSection = document.querySelector('.hero-section');
    
    if (heroImage && heroSection) {
        let ticking = false;
        const isMobile = window.matchMedia('(max-width: 767px)').matches;
        const isTablet = window.matchMedia('(max-width: 991px)').matches;
        
        function updateHeroTransform() {
            const scrollY = window.scrollY || window.pageYOffset;
            const heroHeight = heroSection.offsetHeight;
            const progress = Math.min(scrollY / heroHeight, 1);
            
            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            
            // Base transforms
            const translateY = eased * 300;
            const scale = 1.3 - (eased * 0.5);
            const translateX = eased * (isMobile ? 0 : 50);
            
            // 3D rotation (disabled on mobile)
            let rotateX = 0;
            let rotateY = 0;
            
            if (!isMobile) {
                rotateX = 10 - (eased * 10);
                rotateY = (isTablet ? 3 : 5) - (eased * (isTablet ? 3 : 5));
            }
            
            // Shadow intensity
            const shadowOpacity = 0.6 - (eased * 0.3);
            const shadowBlur = 60 - (eased * 30);
            
            // Apply transform (GPU-accelerated)
            heroImage.style.transform = `
                translateY(${translateY}px)
                translateX(${translateX}px)
                scale(${scale})
                rotateX(${rotateX}deg)
                rotateY(${rotateY}deg)
            `;
            
            heroImage.style.boxShadow = `
                0 ${30 + eased * 20}px ${shadowBlur}px rgba(0, 0, 0, ${shadowOpacity}),
                0 0 0 1px rgba(212, 175, 55, ${0.1 - eased * 0.1})
            `;
            
            // Opacity fade at end
            heroImage.style.opacity = 1 - (progress > 0.8 ? (progress - 0.8) * 5 : 0);
            
            ticking = false;
        }
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(updateHeroTransform);
                ticking = true;
            }
        }, { passive: true });
        
        // Initial state
        heroImage.style.transform = 'translateY(0) translateX(0) scale(1.3) rotateX(10deg) rotateY(5deg)';
    }

    // ============================================
    // STAGGERED FADE-IN FOR DISH CARDS
    // ============================================
    function revealCards() {
        const cards = document.querySelectorAll('.dish-card');
        const windowHeight = window.innerHeight;
        
        cards.forEach((card, index) => {
            const rect = card.getBoundingClientRect();
            if (rect.top < windowHeight * 0.85) {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0) scale(1)';
                }, index * 100);
            }
        });
    }
    
    // Set initial state for cards
    document.querySelectorAll('.dish-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(40px) scale(0.95)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    
    $(window).on('scroll resize', revealCards);
    revealCards();

    // ============================================
    // MENU CATEGORY FILTERING (AJAX)
    // ============================================
    $('.menu-category-btn').click(function() {
        const category = $(this).data('category');
        
        $('.menu-category-btn').removeClass('active');
        $(this).addClass('active');
        
        $.ajax({
            url: 'api/get_menu.php',
            type: 'GET',
            data: { category: category },
            success: function(response) {
                $('#menu-items-container').html(response);
                animateMenuItems();
            },
            error: function() {
                showToast('Error loading menu items', 'error');
            }
        });
    });

    function animateMenuItems() {
        $('.menu-item-row').each(function(index) {
            $(this).hide().delay(index * 80).fadeIn(400);
        });
    }

    // ============================================
    // ADD TO CART
    // ============================================
    $(document).on('click', '.btn-add-cart', function(e) {
        e.preventDefault();
        const btn = $(this);
        const itemId = btn.data('id');
        const qty = btn.closest('.dish-card, .menu-item-row').find('.qty-input').val() || 1;
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: 'api/add_to_cart.php',
            type: 'POST',
            data: {
                item_id: itemId,
                qty: qty,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateCartBadge(response.item_count);
                    showToast(response.message, 'success');
                    
                    // Button feedback
                    btn.html('Added!').addClass('btn-luxe-primary');
                    setTimeout(() => {
                        btn.html('Add to Cart').removeClass('btn-luxe-primary').prop('disabled', false);
                    }, 1500);
                } else {
                    showToast(response.message, 'error');
                    btn.prop('disabled', false).html('Add to Cart');
                }
            },
            error: function() {
                showToast('Error adding to cart', 'error');
                btn.prop('disabled', false).html('Add to Cart');
            }
        });
    });

    // ============================================
    // CART QUANTITY CONTROLS
    // ============================================
    $(document).on('click', '.qty-btn', function() {
        const btn = $(this);
        const input = btn.siblings('.qty-value');
        const itemId = btn.closest('.cart-item').data('id');
        let qty = parseInt(input.text());
        
        if (btn.hasClass('qty-minus')) {
            if (qty > 1) qty--;
            else return; // Don't go below 1
        } else {
            qty++;
        }
        
        input.text(qty);
        
        $.ajax({
            url: 'api/update_cart.php',
            type: 'POST',
            data: {
                item_id: itemId,
                qty: qty,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateCartTotals(response.totals);
                    updateCartBadge(response.totals.item_count);
                }
            }
        });
    });

    // ============================================
    // REMOVE FROM CART
    // ============================================
    $(document).on('click', '.btn-remove-cart', function() {
        const itemId = $(this).closest('.cart-item').data('id');
        
        $.ajax({
            url: 'api/remove_cart.php',
            type: 'POST',
            data: {
                item_id: itemId,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $(`.cart-item[data-id="${itemId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        if ($('.cart-item').length === 0) {
                            location.reload();
                        }
                    });
                    updateCartTotals(response.totals);
                    updateCartBadge(response.totals.item_count);
                    showToast(response.message, 'success');
                }
            }
        });
    });

    // ============================================
    // APPLY COUPON
    // ============================================
    $('#apply-coupon').click(function() {
        const code = $('#coupon-code').val().trim();
        if (!code) return;
        
        const btn = $(this);
        btn.prop('disabled', true).text('Applying...');
        
        $.ajax({
            url: 'api/apply_coupon.php',
            type: 'POST',
            data: {
                code: code,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateCartTotals(response.totals);
                    showToast(response.message, 'success');
                    $('#coupon-display').html(`
                        <div class="d-flex justify-content-between align-items-center bg-success bg-opacity-10 p-2 rounded">
                            <span class="text-success">Coupon: <strong>${code}</strong></span>
                            <button class="btn btn-sm btn-outline-danger border-0" id="remove-coupon">Remove</button>
                        </div>
                    `);
                } else {
                    showToast(response.message, 'error');
                }
                btn.prop('disabled', false).text('Apply');
            },
            error: function() {
                showToast('Error applying coupon', 'error');
                btn.prop('disabled', false).text('Apply');
            }
        });
    });

    $(document).on('click', '#remove-coupon', function() {
        $.ajax({
            url: 'api/remove_coupon.php',
            type: 'POST',
            data: {
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateCartTotals(response.totals);
                    $('#coupon-display').empty();
                    $('#coupon-code').val('');
                    showToast('Coupon removed', 'success');
                }
            }
        });
    });

    // ============================================
    // CHECKOUT FORM
    // ============================================
    $('#checkout-form').submit(function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
        
        $.ajax({
            url: 'api/place_order.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showToast('Order placed successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = `tracking.php?order=${response.order_id}`;
                    }, 1500);
                } else {
                    showToast(response.message, 'error');
                    btn.prop('disabled', false).text('Place Order');
                }
            },
            error: function() {
                showToast('Error placing order', 'error');
                btn.prop('disabled', false).text('Place Order');
            }
        });
    });

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function updateCartBadge(count) {
        const badge = $('.cart-badge');
        if (badge.length) {
            badge.text(count);
            if (count > 0) {
                badge.show();
                badge.addClass('animate-bounce');
                setTimeout(() => badge.removeClass('animate-bounce'), 1000);
            } else {
                badge.hide();
            }
        }
    }

    function updateCartTotals(totals) {
        if (totals) {
            $('#cart-subtotal').text('$' + totals.subtotal.toFixed(2));
            $('#cart-discount').text('-$' + totals.discount.toFixed(2));
            $('#cart-total').text('$' + totals.total.toFixed(2));
        }
    }

    function showToast(message, type = 'info') {
        const toast = $(`
            <div class="toast-luxe ${type}">
                <div class="d-flex align-items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} me-2"></i>
                    <span>${message}</span>
                </div>
            </div>
        `);
        
        $('.toast-container').append(toast);
        
        setTimeout(() => {
            toast.fadeOut(300, function() { $(this).remove(); });
        }, 3000);
    }

    // ============================================
    // PARALLAX EFFECT FOR PARTICLES
    // ============================================
    document.querySelectorAll('.particle').forEach((particle, i) => {
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = (i * 0.5) + 's';
        particle.style.animationDuration = (4 + Math.random() * 4) + 's';
    });

    // ============================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ============================================
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 800);
        }
    });

    // ============================================
    // ADMIN SIDEBAR TOGGLE
    // ============================================
    $('#sidebar-toggle').click(function() {
        $('.admin-sidebar').toggleClass('open');
    });

    $(document).click(function(e) {
        if ($(window).width() <= 991) {
            if (!$(e.target).closest('.admin-sidebar, #sidebar-toggle').length) {
                $('.admin-sidebar').removeClass('open');
            }
        }
    });
});
