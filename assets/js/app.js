/* Perilaku antarmuka: sidebar, treeview, jam, konfirmasi hapus */
(function ($) {
    'use strict';

    // Sidebar (desktop: mini/collapse, mobile: off-canvas)
    $(document).on('click', '.sidebar-toggle', function (e) {
        e.preventDefault();
        if ($(window).width() < 768) {
            $('body').toggleClass('sidebar-open');
        } else {
            $('body').toggleClass('sidebar-collapse');
            try { localStorage.setItem('sb-collapse', $('body').hasClass('sidebar-collapse') ? '1' : '0'); } catch (x) {}
        }
    });
    try {
        if ($(window).width() >= 768 && localStorage.getItem('sb-collapse') === '1') {
            $('body').addClass('sidebar-collapse');
        }
    } catch (x) {}

    // Treeview
    $(document).on('click', '.sidebar-menu li.treeview > a', function (e) {
        var $li = $(this).parent();
        var $sub = $li.children('.treeview-menu');
        if (!$sub.length) { return; }
        e.preventDefault();
        if ($li.hasClass('active')) {
            $sub.slideUp(180, function () { $li.removeClass('active'); $sub.css('display', ''); });
        } else {
            $sub.hide();
            $li.addClass('active');
            $sub.slideDown(180);
        }
    });

    // Jam
    var hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    function p(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
        var d = new Date();
        $('#jam-live').text(hari[d.getDay()] + ', ' + d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear() + ', ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds()));
    }
    if ($('#jam-live').length) { tick(); setInterval(tick, 1000); }

    // Konfirmasi untuk form/tombol dengan data-konfirmasi
    $(document).on('submit', 'form[data-konfirmasi]', function (e) {
        var f = this;
        if (f.dataset.ok === '1') { return; }
        e.preventDefault();
        swal({
            title: 'Konfirmasi',
            text: $(f).data('konfirmasi'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3b6aa6',
            confirmButtonText: 'Ya, lanjutkan',
            cancelButtonText: 'Batal'
        }, function (ok) {
            if (ok) { f.dataset.ok = '1'; f.submit(); }
        });
    });
})(jQuery);
