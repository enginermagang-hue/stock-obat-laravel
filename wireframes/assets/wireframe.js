/* Wireframe UI — shell, navigasi, dan pemilih role. */
(function () {
  'use strict';

  var ALL = ['puskesmas', 'gudang', 'distribusi', 'apoteker', 'admin'];

  var ROLES = [
    { id: 'puskesmas', label: 'Puskesmas' },
    { id: 'gudang', label: 'Gudang' },
    { id: 'distribusi', label: 'Distribusi' },
    { id: 'apoteker', label: 'Apoteker' },
    { id: 'admin', label: 'Admin' }
  ];

  var PAGES = [
    { id: 'index', file: 'index.html', title: 'Hub Wireframe', group: 'Umum', roles: ALL, desc: 'Daftar seluruh layar wireframe.' },
    { id: 'shell', file: '00-shell.html', title: 'Shell & Komponen', group: 'Umum', roles: ALL, desc: 'Layout dasar dan pustaka komponen.' },
    { id: 'login', file: '01-login.html', title: 'Login', group: 'Umum', roles: ALL, desc: 'Halaman masuk untuk semua role.' },
    { id: 'dashboard', file: '02-dashboard.html', title: 'Dashboard', group: 'Utama', roles: ALL, desc: 'Ringkasan stok, anggaran, dan tugas menunggu.' },
    { id: 'notifikasi', file: '22-notifikasi.html', title: 'Notifikasi', group: 'Utama', roles: ALL, desc: 'Daftar notifikasi database.' },

    { id: 'master-obat', file: '03-master-obat.html', title: 'Master Obat', group: 'Master Data', roles: ['gudang', 'distribusi', 'apoteker', 'admin'], desc: 'Daftar obat, stok minimum, golongan, izin edar.' },
    { id: 'master-kategori', file: '04-master-kategori.html', title: 'Kategori Obat', group: 'Master Data', roles: ['gudang', 'apoteker', 'admin'], desc: 'Kategori dan pengelompokan obat.' },
    { id: 'master-supplier', file: '05-master-supplier.html', title: 'Supplier', group: 'Master Data', roles: ['gudang', 'apoteker', 'admin'], desc: 'Data pemasok dan izin usaha.' },
    { id: 'master-facilities', file: '06-master-facilities.html', title: 'Puskesmas / Fasilitas', group: 'Master Data', roles: ['apoteker', 'admin'], desc: 'Daftar puskesmas dan akun login.' },
    { id: 'master-sumber-dana', file: '07-master-sumber-dana.html', title: 'Sumber Dana', group: 'Master Data', roles: ['apoteker', 'admin'], desc: 'APBN, APBD, BOK, DAK, dll.' },
    { id: 'master-licenses', file: '08-master-licenses.html', title: 'Izin & CDOB', group: 'Master Data', roles: ['apoteker', 'admin'], desc: 'Izin apotek, izin edar, dan CDOB.' },

    { id: 'stok-batch', file: '09-stok-batch.html', title: 'Batch Obat', group: 'Stok', roles: ['gudang', 'apoteker', 'admin'], desc: 'Batch, expiry, lokasi rak, jumlah sisa.' },
    { id: 'stok-ledger', file: '10-stok-ledger.html', title: 'Stock Ledger', group: 'Stok', roles: ['gudang', 'apoteker', 'admin'], desc: 'Jurnal mutasi append-only.' },
    { id: 'stok-adjustment', file: '11-stok-adjustment.html', title: 'Penyesuaian Stok', group: 'Stok', roles: ['gudang', 'apoteker', 'admin'], desc: 'Koreksi, expired, rusak, hilang, retur.' },
    { id: 'stok-opname', file: '12-stok-opname.html', title: 'Stok Opname', group: 'Stok', roles: ['gudang', 'apoteker', 'admin'], desc: 'Sesi opname, mode buta, verifikasi selisih.' },

    { id: 'lplpo', file: '13-lplpo.html', title: 'LPLPO', group: 'Perencanaan', roles: ['puskesmas', 'apoteker', 'admin'], desc: 'Laporan pemakaian & stok per puskesmas.' },
    { id: 'permintaan', file: '14-permintaan.html', title: 'Permintaan Obat', group: 'Perencanaan', roles: ['puskesmas', 'apoteker', 'admin'], desc: 'Permintaan dari LPLPO atau mandiri + verifikasi.' },
    { id: 'rko', file: '15-rko.html', title: 'RKO', group: 'Perencanaan', roles: ['apoteker', 'admin'], desc: 'Rencana kebutuhan obat, snapshot & versi.' },
    { id: 'prediksi', file: '16-prediksi.html', title: 'Prediksi Kebutuhan', group: 'Perencanaan', roles: ['puskesmas', 'apoteker', 'admin'], desc: 'Hasil prediksi, interval, MAPE, metode.' },

    { id: 'anggaran', file: '17-anggaran.html', title: 'Anggaran', group: 'Pengadaan', roles: ['apoteker', 'admin'], desc: 'Pagu per sumber dana dan unit + realisasi.' },
    { id: 'purchase-order', file: '18-purchase-order.html', title: 'Purchase Order', group: 'Pengadaan', roles: ['gudang', 'apoteker', 'admin'], desc: 'PO, approve dengan override pagu, penerimaan.' },
    { id: 'goods-receipt', file: '19-goods-receipt.html', title: 'Goods Receipt', group: 'Pengadaan', roles: ['gudang', 'apoteker', 'admin'], desc: 'Penerimaan barang → batch + ledger masuk.' },

    { id: 'distribusi', file: '20-distribusi.html', title: 'Distribusi', group: 'Distribusi', roles: ALL, desc: 'Surat jalan FEFO dan konfirmasi terima.' },

    { id: 'laporan', file: '21-laporan.html', title: 'Laporan', group: 'Administrasi', roles: ALL, desc: '16 laporan + contoh layout.' },
    { id: 'audit-log', file: '23-audit-log.html', title: 'Audit Log', group: 'Administrasi', roles: ['apoteker', 'admin'], desc: 'Riwayat perubahan bersifat read-only.' },
    { id: 'pengaturan', file: '24-pengaturan.html', title: 'Pengaturan', group: 'Administrasi', roles: ['admin'], desc: 'User, role, dan permission.' }
  ];

  var NAV = [
    {
      group: 'Utama',
      items: [
        { id: 'dashboard', roles: ALL },
        { id: 'notifikasi', roles: ALL }
      ]
    },
    {
      group: 'Master Data',
      items: [
        { id: 'master-obat', roles: ['gudang', 'distribusi', 'apoteker', 'admin'] },
        { id: 'master-kategori', roles: ['gudang', 'apoteker', 'admin'] },
        { id: 'master-supplier', roles: ['gudang', 'apoteker', 'admin'] },
        { id: 'master-facilities', roles: ['apoteker', 'admin'] },
        { id: 'master-sumber-dana', roles: ['apoteker', 'admin'] },
        { id: 'master-licenses', roles: ['apoteker', 'admin'] }
      ]
    },
    {
      group: 'Stok',
      items: [
        { id: 'stok-batch', roles: ['gudang', 'apoteker', 'admin'] },
        { id: 'stok-ledger', roles: ['gudang', 'apoteker', 'admin'] },
        { id: 'stok-adjustment', roles: ['gudang', 'apoteker', 'admin'] },
        { id: 'stok-opname', roles: ['gudang', 'apoteker', 'admin'] }
      ]
    },
    {
      group: 'Perencanaan',
      items: [
        { id: 'lplpo', roles: ['puskesmas', 'apoteker', 'admin'] },
        { id: 'permintaan', roles: ['puskesmas', 'apoteker', 'admin'] },
        { id: 'rko', roles: ['apoteker', 'admin'] },
        { id: 'prediksi', roles: ['puskesmas', 'apoteker', 'admin'] }
      ]
    },
    {
      group: 'Pengadaan',
      items: [
        { id: 'anggaran', roles: ['apoteker', 'admin'] },
        { id: 'purchase-order', roles: ['gudang', 'apoteker', 'admin'] },
        { id: 'goods-receipt', roles: ['gudang', 'apoteker', 'admin'] }
      ]
    },
    {
      group: 'Distribusi',
      items: [
        { id: 'distribusi', roles: ALL }
      ]
    },
    {
      group: 'Administrasi',
      items: [
        { id: 'laporan', roles: ALL },
        { id: 'audit-log', roles: ['apoteker', 'admin'] },
        { id: 'pengaturan', roles: ['admin'] }
      ]
    }
  ];

  function pageById(id) {
    return PAGES.filter(function (p) { return p.id === id; })[0];
  }

  function currentRole() {
    var saved = null;
    try { saved = window.localStorage.getItem('wireframe-role'); } catch (e) {}
    return ROLES.some(function (r) { return r.id === saved; }) ? saved : 'apoteker';
  }

  function setRole(role) {
    try { window.localStorage.setItem('wireframe-role', role); } catch (e) {}
    applyRole(role);
  }

  function applyRole(role) {
    document.querySelectorAll('[data-roles]').forEach(function (el) {
      var allowed = el.getAttribute('data-roles').split(',').map(function (s) { return s.trim(); });
      el.style.display = allowed.indexOf(role) === -1 ? 'none' : '';
    });
    renderNav(role);
    var select = document.getElementById('role-select');
    if (select) select.value = role;
  }

  function renderNav(role) {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    var activeId = document.body.getAttribute('data-page');
    var html = '<div class="brand">ObatStock<small>Wireframe UI · ' + role + '</small></div>';
    NAV.forEach(function (group) {
      var items = group.items.filter(function (item) {
        return item.roles.indexOf(role) !== -1;
      });
      if (!items.length) return;
      html += '<div class="nav-group">' + group.group + '</div>';
      items.forEach(function (item) {
        var p = pageById(item.id);
        if (!p) return;
        html += '<a class="nav-item' + (item.id === activeId ? ' active' : '') + '" href="' + p.file + '">' + p.title + '</a>';
      });
    });
    html += '<div class="nav-group">Wireframe</div>';
    html += '<a class="nav-item' + (activeId === 'index' ? ' active' : '') + '" href="index.html">Hub semua layar</a>';
    html += '<a class="nav-item' + (activeId === 'shell' ? ' active' : '') + '" href="00-shell.html">Shell & komponen</a>';
    sidebar.innerHTML = html;
  }

  function buildShell(page) {
    var app = document.createElement('div');
    app.className = 'app';
    app.innerHTML =
      '<aside class="sidebar" id="sidebar"></aside>' +
      '<div class="main">' +
      '<header class="topbar">' +
      '<div class="crumb" id="crumb"></div>' +
      '<div class="spacer"></div>' +
      '<span class="topbar-label">Role:</span>' +
      '<select class="role-select" id="role-select">' +
      ROLES.map(function (r) { return '<option value="' + r.id + '">' + r.label + '</option>'; }).join('') +
      '</select>' +
      '<div class="avatar">A</div>' +
      '</div>' +
      '<div class="content" id="content"></div>' +
      '</div>';

    var existing = document.querySelector('.page');
    document.body.insertBefore(app, document.body.firstChild);
    var main = document.querySelector('.app .main');
    var content = document.getElementById('content');
    main.appendChild(content);
    if (existing) content.appendChild(existing);

    document.getElementById('role-select').addEventListener('change', function (e) {
      setRole(e.target.value);
    });

    var crumb = document.getElementById('crumb');
    if (page && page.group) {
      crumb.innerHTML = 'Wireframe / ' + page.group + ' / <strong>' + page.title + '</strong>';
    }
  }

  function renderHub() {
    var host = document.getElementById('hub');
    if (!host) return;
    var groups = [];
    PAGES.forEach(function (p) {
      if (p.id === 'login') return;
      var g = groups.filter(function (x) { return x.name === p.group; })[0];
      if (!g) { g = { name: p.group, pages: [] }; groups.push(g); }
      g.pages.push(p);
    });
    host.innerHTML = groups.map(function (g) {
      return '<section style="margin-bottom:26px">' +
        '<h2>' + g.name + '</h2>' +
        '<div class="hub-grid">' + g.pages.map(function (p) {
          return '<a class="hub-card" href="' + p.file + '" data-roles="' + p.roles.join(',') + '">' +
            '<div class="g">' + p.group + '</div>' +
            '<div class="t">' + p.title + '</div>' +
            '<div class="d">' + p.desc + '</div>' +
            '</a>';
        }).join('') + '</div></section>';
    }).join('');
  }

  function init() {
    var id = document.body.getAttribute('data-page') || 'index';
    var page = pageById(id);

    if (id === 'login') {
      document.body.classList.add('bare');
      applyRole(currentRole());
      return;
    }

    buildShell(page);
    if (id === 'index') renderHub();
    applyRole(currentRole());
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
