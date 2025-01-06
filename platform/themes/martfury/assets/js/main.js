(function ($) {
    'use strict';

    function backgroundImage() {
        let dataBackground = $('[data-background]');
        dataBackground.each(function () {
            if ($(this).attr('data-background')) {
                let imagePath = $(this).attr('data-background');
                $(this).css({
                    'background-image': 'url(' + imagePath + ')',
                    'background-color': '#fff'
                });
            }
        });
    }

    function siteToggleAction() {
        let navSidebar = $('.navigation--sidebar'),
            filterSidebar = $('.ps-filter--sidebar');
        $('.menu-toggle-open').on('click', function (e) {
            e.preventDefault();
            $(this).toggleClass('active');
            navSidebar.toggleClass('active');
            $('.ps-site-overlay').toggleClass('active');
        });

        $('.ps-toggle--sidebar').on('click', function (e) {
            e.preventDefault();
            let url = $(this).attr('href');
            $(this).toggleClass('active');
            $(this)
                .siblings('a')
                .removeClass('active');
            $(url).toggleClass('active');
            $(url)
                .siblings('.ps-panel--sidebar')
                .removeClass('active');
            if ($(this).hasClass('active')) {
                $('.ps-site-overlay').addClass('active');
            } else {
                $('.ps-site-overlay').removeClass('active');
            }
        });

        $('#filter-sidebar').on('click', function (e) {
            e.preventDefault();
            filterSidebar.addClass('active');
            $('.ps-site-overlay').addClass('active');
        });

        $('.ps-filter--sidebar .ps-filter__header .ps-btn--close').on(
            'click',
            function (e) {
                e.preventDefault();
                filterSidebar.removeClass('active');
                $('.ps-site-overlay').removeClass('active');
            }
        );

        $('body').on('click', function (e) {
            if (
                $(e.target)
                    .siblings('.ps-panel--sidebar')
                    .hasClass('active')
            ) {
                $('.ps-panel--sidebar').removeClass('active');
                $('.ps-site-overlay').removeClass('active');
            }
        });
    }

    function subMenuToggle() {
        $('.menu--mobile .menu-item-has-children > .sub-toggle').on(
            'click',
            function (e) {
                e.preventDefault();
                let current = $(this).parent('.menu-item-has-children');
                $(this).toggleClass('active');
                current
                    .siblings()
                    .find('.sub-toggle')
                    .removeClass('active');
                current.children('.sub-menu').slideToggle(350);
                current
                    .siblings()
                    .find('.sub-menu')
                    .slideUp(350);
                if (current.hasClass('has-mega-menu')) {
                    current.children('.mega-menu').slideToggle(350);
                    current
                        .siblings('.has-mega-menu')
                        .find('.mega-menu')
                        .slideUp(350);
                }
            }
        );
        $('.menu--mobile .has-mega-menu .mega-menu__column .sub-toggle').on(
            'click',
            function (e) {
                e.preventDefault();
                let current = $(this).closest('.mega-menu__column');
                $(this).toggleClass('active');
                current
                    .siblings()
                    .find('.sub-toggle')
                    .removeClass('active');
                current.children('.mega-menu__list').slideToggle(350);
                current
                    .siblings()
                    .find('.mega-menu__list')
                    .slideUp(350);
            }
        );

        let $listCategories = $(document).find('.widget-product-categories');
        if ($listCategories.length > 0) {
            $(document).on(
                'click',
                '.widget-product-categories .menu-item-has-children > .sub-toggle',
                function (e) {
                    e.preventDefault();
                    let current = $(this).parent('.menu-item-has-children');
                    $(this).toggleClass('active');
                    current
                        .siblings()
                        .find('.sub-toggle')
                        .removeClass('active');
                    current.children('.sub-menu').slideToggle(350);
                    current
                        .siblings()
                        .find('.sub-menu')
                        .slideUp(350);
                    if (current.hasClass('has-mega-menu')) {
                        current.children('.mega-menu').slideToggle(350);
                        current
                            .siblings('.has-mega-menu')
                            .find('.mega-menu')
                            .slideUp(350);
                    }
                }
            );
        }
    }

    function stickyHeader() {
        let header = $('.header'),
            checkpoint = 50;
        header.each(function () {
            if ($(this).data('sticky') === true) {
                let el = $(this);
                $(window).scroll(function () {
                    let currentPosition = $(this).scrollTop();
                    if (currentPosition > checkpoint) {
                        el.addClass('header--sticky');
                    } else {
                        el.removeClass('header--sticky');
                    }
                });
            }
        });

        let stickyCart = $('#cart-sticky');
        if (stickyCart.length > 0) {
            $(window).scroll(function () {
                let currentPosition = $(this).scrollTop();
                if (currentPosition > checkpoint) {
                    stickyCart.addClass('active');
                } else {
                    stickyCart.removeClass('active');
                }
            });
        }
    }

    function owlCarouselConfig() {
        let target = $('.owl-slider');
        if (target.length > 0) {
            target.each(function () {
                let el = $(this),
                    dataAuto = el.data('owl-auto'),
                    dataLoop = el.data('owl-loop'),
                    dataSpeed = el.data('owl-speed'),
                    dataGap = el.data('owl-gap'),
                    dataNav = el.data('owl-nav'),
                    dataDots = el.data('owl-dots'),
                    dataAnimateIn = el.data('owl-animate-in')
                        ? el.data('owl-animate-in')
                        : '',
                    dataAnimateOut = el.data('owl-animate-out')
                        ? el.data('owl-animate-out')
                        : '',
                    dataDefaultItem = el.data('owl-item'),
                    dataItemXS = el.data('owl-item-xs'),
                    dataItemSM = el.data('owl-item-sm'),
                    dataItemMD = el.data('owl-item-md'),
                    dataItemLG = el.data('owl-item-lg'),
                    dataItemXL = el.data('owl-item-xl'),
                    dataNavLeft = el.data('owl-nav-left')
                        ? el.data('owl-nav-left')
                        : "<i class='icon-chevron-left'></i>",
                    dataNavRight = el.data('owl-nav-right')
                        ? el.data('owl-nav-right')
                        : "<i class='icon-chevron-right'></i>",
                    duration = el.data('owl-duration'),
                    datamouseDrag =
                        el.data('owl-mousedrag') == 'on' ? true : false;
                if (
                    target.children('div, span, a, img, h1, h2, h3, h4, h5, h5')
                        .length >= 2
                ) {
                    el.addClass('owl-carousel').owlCarousel({
                        rtl: $('body').prop('dir') === 'rtl',
                        animateIn: dataAnimateIn,
                        animateOut: dataAnimateOut,
                        margin: dataGap,
                        autoplay: dataAuto,
                        autoplayTimeout: dataSpeed,
                        autoplayHoverPause: true,
                        loop: dataLoop,
                        nav: dataNav,
                        mouseDrag: datamouseDrag,
                        touchDrag: true,
                        autoplaySpeed: duration,
                        navSpeed: duration,
                        dotsSpeed: duration,
                        dragEndSpeed: duration,
                        navText: [dataNavLeft, dataNavRight],
                        dots: dataDots,
                        items: dataDefaultItem,
                        responsive: {
                            0: {
                                items: dataItemXS,
                            },
                            480: {
                                items: dataItemSM,
                            },
                            768: {
                                items: dataItemMD,
                            },
                            992: {
                                items: dataItemLG,
                            },
                            1200: {
                                items: dataItemXL,
                            },
                            1680: {
                                items: dataDefaultItem,
                            },
                        },
                    });
                }
            });
        }
    }

    function mapConfig() {
        let map = $('#contact-map');
        if (map.length > 0) {
            map.gmap3({
                address: map.data('address'),
                zoom: map.data('zoom'),
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                scrollwheel: false,
            })
                .marker(function (map) {
                    return {
                        position: map.getCenter(),
                        icon: 'img/marker.png',
                    };
                })
                .infowindow({
                    content: map.data('address'),
                })
                .then(function (infowindow) {
                    let map = this.get(0);
                    let marker = this.get(1);
                    marker.addListener('click', function () {
                        infowindow.open(map, marker);
                    });
                });
        } else {
            return false;
        }
    }

    function slickConfig() {
        let product = $('.ps-product--detail');
        if (product.length > 0) {
            let primary = product.find('.ps-product__gallery'),
                second = product.find('.ps-product__variants'),
                vertical = product
                    .find('.ps-product__thumbnail')
                    .data('vertical');
            primary.slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                rtl: $('body').prop('dir') === 'rtl',
                asNavFor: '.ps-product__variants',
                fade: true,
                dots: false,
                infinite: false,
                arrows: primary.data('arrow'),
                prevArrow: "<button class='slick-prev slick-arrow'><i class='fa fa-angle-left'></i></button>",
                nextArrow: "<button class='slick-next slick-arrow'><i class='fa fa-angle-right'></i></button>",
            });
            second.slick({
                slidesToShow: second.data('item'),
                slidesToScroll: 1,
                rtl: $('body').prop('dir') === 'rtl',
                infinite: false,
                arrows: second.data('arrow'),
                focusOnSelect: true,
                prevArrow: "<button class='slick-prev slick-arrow'><i class='fa fa-angle-up'></i></button>",
                nextArrow: "<button class='slick-next slick-arrow'><i class='fa fa-angle-down'></i></button>",
                asNavFor: '.ps-product__gallery',
                vertical: vertical,
                responsive: [
                    {
                        breakpoint: 1200,
                        settings: {
                            arrows: second.data('arrow'),
                            slidesToShow: 4,
                            vertical: false,
                            prevArrow:
                                "<button class='slick-prev slick-arrow'><i class='fa fa-angle-left'></i></button>",
                            nextArrow:
                                "<button class='slick-next slick-arrow'><i class='fa fa-angle-right'></i></button>",
                        },
                    },
                    {
                        breakpoint: 992,
                        settings: {
                            arrows: second.data('arrow'),
                            slidesToShow: 4,
                            vertical: false,
                            prevArrow:
                                "<button class='slick-prev slick-arrow'><i class='fa fa-angle-left'></i></button>",
                            nextArrow:
                                "<button class='slick-next slick-arrow'><i class='fa fa-angle-right'></i></button>",
                        },
                    },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 3,
                            vertical: false,
                            prevArrow:
                                "<button class='slick-prev slick-arrow'><i class='fa fa-angle-left'></i></button>",
                            nextArrow:
                                "<button class='slick-next slick-arrow'><i class='fa fa-angle-right'></i></button>",
                        },
                    },
                ],
            });
        }
    }

    function tabs() {
        $('.ps-tab-list  li > a ').on('click', function (e) {
            e.preventDefault();
            let target = $(this).attr('href');
            $(this)
                .closest('li')
                .siblings('li')
                .removeClass('active');
            $(this)
                .closest('li')
                .addClass('active');
            $(target).addClass('active');
            $(target)
                .siblings('.ps-tab')
                .removeClass('active');
            $('.ps-tab-list li').removeClass('active');
            $('.ps-tab-list li a[href="' + target + '"]').closest('li').addClass('active');

            $('html, body').animate(
                {
                    scrollTop: ($(target).offset().top - $('.header--product .navigation').height() - 165) + 'px',
                },
                800
            );
        });
        $('.ps-tab-list.owl-slider .owl-item a').on('click', function (e) {
            e.preventDefault();
            let target = $(this).attr('href');
            $(this)
                .closest('.owl-item')
                .siblings('.owl-item')
                .removeClass('active');
            $(this)
                .closest('.owl-item')
                .addClass('active');
            $(target).addClass('active');
            $(target)
                .siblings('.ps-tab')
                .removeClass('active');
        });
    }

    function rating() {
        $('select.ps-rating').each(function () {
            let readOnly;
            if ($(this).attr('data-read-only') == 'true') {
                readOnly = true;
            } else {
                readOnly = false;
            }
            $(this).barrating({
                theme: 'fontawesome-stars',
                readonly: readOnly,
                emptyValue: '0',
            });
        });
    }

    function productLightbox() {
        let product = $('.ps-product--detail');
        if (product.length > 0) {
            $('.ps-product__gallery').lightGallery({
                selector: '.item a',
                thumbnail: true,
                share: false,
                fullScreen: false,
                autoplay: false,
                autoplayControls: false,
                actualSize: false,
            });
            if (product.hasClass('ps-product--sticky')) {
                $('.ps-product__thumbnail').lightGallery({
                    selector: '.item a',
                    thumbnail: true,
                    share: false,
                    fullScreen: false,
                    autoplay: false,
                    autoplayControls: false,
                    actualSize: false,
                });
            }
        }
        $('.ps-gallery--image').lightGallery({
            selector: '.ps-gallery__item',
            thumbnail: true,
            share: false,
            fullScreen: false,
            autoplay: false,
            autoplayControls: false,
            actualSize: false,
        });
        $('.ps-video').lightGallery({
            thumbnail: false,
            share: false,
            fullScreen: false,
            autoplay: false,
            autoplayControls: false,
            actualSize: false,
        });
    }

    function backToTop() {
        let scrollPos = 0;
        let element = $('#back2top');
        $(window).scroll(function () {
            let scrollCur = $(window).scrollTop();
            if (scrollCur > scrollPos) {
                // scroll down
                if (scrollCur > 500) {
                    element.addClass('active');
                } else {
                    element.removeClass('active');
                }
            } else {
                // scroll up
                element.removeClass('active');
            }

            scrollPos = scrollCur;
        });

        element.on('click', function () {
            $('html, body').animate(
                {
                    scrollTop: '0px',
                },
                800
            );
        });
    }

    function modalInit() {
        let modal = $('.ps-modal');
        if (modal.length) {
            if (modal.hasClass('active')) {
                $('body').css('overflow-y', 'hidden');
            }
        }
        modal.find('.ps-modal__close, .ps-btn--close').on('click', function (e) {
            e.preventDefault();
            $(this)
                .closest('.ps-modal')
                .removeClass('active');
        });
        $('.ps-modal-link').on('click', function (e) {
            e.preventDefault();
            let target = $(this).attr('href');
            $(target).addClass('active');
            $('body').css('overflow-y', 'hidden');
        });
        $('.ps-modal').on('click', function (event) {
            if (!$(event.target).closest('.ps-modal__container').length) {
                modal.removeClass('active');
                $('body').css('overflow-y', 'auto');
            }
        });
    }

    function searchInit() {
        let searchbox = $('.ps-search');
        $('.ps-search-btn').on('click', function (e) {
            e.preventDefault();
            searchbox.addClass('active');
        });
        searchbox.find('.ps-btn--close').on('click', function (e) {
            e.preventDefault();
            searchbox.removeClass('active');
        });
    }

    function countDown() {
        let time = $('.ps-countdown');
        time.each(function () {
            let el = $(this),
                value = $(this).data('time');
            let countDownDate = new Date(value).getTime();
            let timeout = setInterval(function () {
                let now = new Date().getTime(),
                    distance = countDownDate - now;
                let days = Math.floor(distance / (1000 * 60 * 60 * 24)),
                    hours = Math.floor(
                        (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
                    ),
                    minutes = Math.floor(
                        (distance % (1000 * 60 * 60)) / (1000 * 60)
                    ),
                    seconds = Math.floor((distance % (1000 * 60)) / 1000);
                el.find('.days').html(days < 10 ? '0' + days : days);
                el.find('.hours').html(hours < 10 ? '0' + hours : hours);
                el.find('.minutes').html(minutes < 10 ? '0' + minutes : minutes);
                el.find('.seconds').html(seconds < 10 ? '0' + seconds : seconds);
                if (distance < 0) {
                    clearInterval(timeout);
                    el.closest('.ps-section').hide();
                }
            }, 1000);
        });
    }

    function productFilterToggle() {
        $('.ps-filter__trigger').on('click', function (e) {
            e.preventDefault();
            let el = $(this);
            el.find('.ps-filter__icon').toggleClass('active');
            el.closest('.ps-filter')
                .find('.ps-filter__content')
                .slideToggle();
        });
        if ($('.ps-sidebar--home').length > 0) {
            $('.ps-sidebar--home > .ps-sidebar__header > a').on(
                'click',
                function (e) {
                    e.preventDefault();
                    $(this)
                        .closest('.ps-sidebar--home')
                        .children('.ps-sidebar__content')
                        .slideToggle();
                }
            );
        }
    }

    function mainSlider() {
        let homeBanner = $('.ps-carousel--animate');
        homeBanner.slick({
            autoplay: true,
            rtl: $('body').prop('dir') === 'rtl',
            speed: 1000,
            lazyLoad: 'progressive',
            arrows: false,
            fade: true,
            dots: true,
            prevArrow: "<i class='slider-prev ba-back'></i>",
            nextArrow: "<i class='slider-next ba-next'></i>",
        });
    }

    function subscribePopup() {
        let subscribe = $('#subscribe'),
            time = subscribe.data('time');
        setTimeout(function () {
            if (subscribe.length > 0) {
                subscribe.addClass('active');
                $('body').css('overflow', 'hidden');
            }
        }, time);
        $('.ps-popup__close').on('click', function (e) {
            e.preventDefault();
            $(this)
                .closest('.ps-popup')
                .removeClass('active');
            $('body').css('overflow', 'auto');
        });
        $('#subscribe').on('click', function (event) {
            if (!$(event.target).closest('.ps-popup__content').length) {
                subscribe.removeClass('active');
                $('body').css('overflow-y', 'auto');
            }
        });
    }

    function stickySidebar() {
        let sticky = $('.ps-product--sticky'),
            stickySidebar,
            checkPoint = 992,
            windowWidth = $(window).innerWidth();
        if (sticky.length > 0) {
            stickySidebar = new StickySidebar(
                '.ps-product__sticky .ps-product__info',
                {
                    topSpacing: 20,
                    bottomSpacing: 20,
                    containerSelector: '.ps-product__sticky',
                }
            );
            if ($('.sticky-2').length > 0) {
                let stickySidebar2 = new StickySidebar(
                    '.ps-product__sticky .sticky-2',
                    {
                        topSpacing: 20,
                        bottomSpacing: 20,
                        containerSelector: '.ps-product__sticky',
                    }
                );
            }
            if (checkPoint > windowWidth) {
                stickySidebar.destroy();
                stickySidebar2.destroy();
            }
        } else {
            return false;
        }
    }

    function accordion() {
        let accordion = $('.ps-accordion');
        accordion.find('.ps-accordion__content').hide();
        $('.ps-accordion.active')
            .find('.ps-accordion__content')
            .show();
        accordion.find('.ps-accordion__header').on('click', function (e) {
            e.preventDefault();
            if (
                $(this)
                    .closest('.ps-accordion')
                    .hasClass('active')
            ) {
                $(this)
                    .closest('.ps-accordion')
                    .removeClass('active');
                $(this)
                    .closest('.ps-accordion')
                    .find('.ps-accordion__content')
                    .slideUp(350);
            } else {
                $(this)
                    .closest('.ps-accordion')
                    .addClass('active');
                $(this)
                    .closest('.ps-accordion')
                    .find('.ps-accordion__content')
                    .slideDown(350);
                $(this)
                    .closest('.ps-accordion')
                    .siblings('.ps-accordion')
                    .find('.ps-accordion__content')
                    .slideUp();
            }
            $(this)
                .closest('.ps-accordion')
                .siblings('.ps-accordion')
                .removeClass('active');
            $(this)
                .closest('.ps-accordion')
                .siblings('.ps-accordion')
                .find('.ps-accordion__content')
                .slideUp();
        });
    }

    function progressBar() {
        let progress = $('.ps-progress');
        progress.each(function () {
            let value = $(this).data('value');
            $(this)
                .find('span')
                .css({
                    width: value + '%',
                });
        });
    }

    function select2Config() {
        $('select.ps-select').select2({
            placeholder: $(this).data('placeholder'),
            minimumResultsForSearch: -1,
            templateSelection: function (state) {
                return jQuery.trim(state.text);
            }
        });
    }

    function carouselNavigation() {
        let prevBtn = $('.ps-carousel__prev'),
            nextBtn = $('.ps-carousel__next');
        prevBtn.on('click', function (e) {
            e.preventDefault();
            let target = $(this).attr('href');
            $(target).trigger('prev.owl.carousel', [1000]);
        });
        nextBtn.on('click', function (e) {
            e.preventDefault();
            let target = $(this).attr('href');
            $(target).trigger('next.owl.carousel', [1000]);
        });
    }

    function filterSlider() {
        let nonLinearSlider = document.getElementById('nonlinear');
        if (typeof nonLinearSlider != 'undefined' && nonLinearSlider != null) {
            noUiSlider.create(nonLinearSlider, {
                connect: true,
                behaviour: 'tap',
                start: [0, 1000],
                range: {
                    min: 0,
                    '10%': 100,
                    '20%': 200,
                    '30%': 300,
                    '40%': 400,
                    '50%': 500,
                    '60%': 600,
                    '70%': 700,
                    '80%': 800,
                    '90%': 900,
                    max: 1000,
                },
            });
            let nodes = [
                document.querySelector('.ps-slider__min'),
                document.querySelector('.ps-slider__max'),
            ];
            nonLinearSlider.noUiSlider.on('update', function (values, handle) {
                nodes[handle].innerHTML = Math.round(values[handle]);
            });
        }
    }

    function handleLiveSearch() {
        $('body').on('click', function (e) {
            if (
                $(e.target).closest('.ps-form--search-header') ||
                e.target.className === '.ps-form--search-header'
            ) {
                $('.ps-panel--search-result').removeClass('active');
            }
        });
    }

    const reviewList = function () {
        let $reviewListWrapper = $('body').find('.comment-list');
        const $loadingSpinner = $('body').find('.loading-spinner');

        $loadingSpinner.addClass('d-none');

        const fetchData = (url, hasAnimation = false) => {
            $.ajax({
                url: url,
                type: 'GET',
                beforeSend: function () {
                    $loadingSpinner.removeClass('d-none');

                    if (hasAnimation) {
                        $('html, body').animate({
                            scrollTop: `${$('.product-reviews-container').offset().top}px`,
                        }, 1500);
                    }
                },
                success: function (res) {
                    $reviewListWrapper.html(res.data);
                    $('.product-reviews-container .product-reviews-header').html(res.message);

                    let $galleries = $('.product-reviews-container .block__images');
                    if ($galleries.length) {
                        $galleries.map((index, value) => {
                            if (!$(value).data('lightGallery')) {
                                $(value).lightGallery({
                                    selector: 'a',
                                    thumbnail: true,
                                    share: false,
                                    fullScreen: false,
                                    autoplay: false,
                                    autoplayControls: false,
                                    actualSize: false,
                                });
                            }
                        });
                    }
                }, complete: function () {
                    $loadingSpinner.addClass('d-none');
                }
            })
        }

        if ($reviewListWrapper.length < 1) {
            return;
        }

        fetchData($reviewListWrapper.data('url'));

        $reviewListWrapper.on('click', '.ps-pagination ul li.page-item a', function (e) {
            e.preventDefault()

            const href = $(this).attr('href')

            if (href === '#') {
                return
            }

            fetchData(href, true)
        })
    }

    $(function () {
        backgroundImage();
        owlCarouselConfig();
        siteToggleAction();
        subMenuToggle();
        productFilterToggle();
        tabs();
        slickConfig();
        productLightbox();
        rating();
        backToTop();
        stickyHeader();
        mapConfig();
        modalInit();
        searchInit();
        countDown();
        mainSlider();
        stickySidebar();
        accordion();
        progressBar();
        select2Config();
        carouselNavigation();
        filterSlider();
        handleLiveSearch();
        reviewList()
    });

    $('[data-toggle="tooltip"]').tooltip();

    $('#product-quickview').on('shown.bs.modal', function () {
        $('.ps-product--quickview .ps-product__images').slick('setPosition');
    });

    $(window).on('load', function () {
        $('body').addClass('loaded');
    });

    let collapseBreadcrumb = function () {
        $('ul.breadcrumb li').each(function () {
            let $this = $(this);
            if (!$this.is(':first-child') && !$this.is(':nth-child(2)') && !$this.is(':last-child')) {
                if (!$this.is(':nth-child(3)')) {
                    $this.find('a').closest('li').hide();
                } else {
                    $this.find('a').hide();
                    $this.find('.extra-breadcrumb-name').text('...').show();
                }
            }
        });
    }

    if ($(window).width() < 768) {
        collapseBreadcrumb();
    }

    $(window).on('resize', function () {
        collapseBreadcrumb();
    });
})(jQuery);
