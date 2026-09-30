/* =============================================================================
   WebDanışmanı — Dosya Yöneticisi kabuğu            sürüm 1.1

   Kurulum yeri : /usr/local/hestia/web/fm/dist/js/wd-fm.js
   Yükleme      : fm/configuration.php -> add_to_body (wd-fm.css ile birlikte)
   Güncelleme   : YENİ dosyadır, HestiaCP güncellemesinde silinmez.

   NE EKLER?
     1. Panelin koyu sol menüsü — dosya yöneticisi panelden kopuk bir sayfa
        olmaktan çıkar.
     2. Kalıcı klasör ağacı — FileGator'da ağaç yalnızca bir modaldır
        (Tree.vue); burada kalıcı hâle getiriliyor.
     3. Kalıcı araç çubuğu — FileGator'ın kendi eylemleri satır sonundaki
        "⋯" menüsüne ve seçim yapılınca beliren gizli bir şeride saklıdır.
        Tasarım bunları sürekli görünen tek bir çubuk istiyor.
     4. Gezinme satırı — üst dizin / ana dizin / tıklanabilir yol / yenile.
     5. Durum çubuğu — klasör+dosya sayısı, seçim, bulunulan yol.
     6. Dosya türüne göre renkli ikonlar.

   ÇUBUKLAR NEDEN #browser İÇİNE, MENÜ VE AĞAÇ <body> ALTINA?
     Menü ile ağaç sabit konumlu yan panellerdir; sayfa akışının dışında
     durmaları gerekir. Araç çubuğu ile durum çubuğu ise içerikle birlikte
     akmalı (tasarımda kartın başlığı ve altlığı). Bu yüzden #browser içine
     konur. Vue yeniden çizerken bunları silebilir; MutationObserver geri
     koyar. Aynı desen Hestia'nın kendi add_to_body betiğinde de kullanılır.

   FILEGATOR'A NASIL BAĞLANIYOR?
     Vue 2, kök örneği DOM'a `el.__vue__` olarak asar. Oradan `$store`
     (cwd.location + içerik) okunur, eylemler ise Browser bileşeninin KENDİ
     metotlarıyla çalıştırılır — böylece uygulamanın akışı (onay kutuları,
     yükleme göstergesi, hata yönetimi, CSRF) olduğu gibi korunur. Kendi API
     çağrımızı yapmıyoruz.

   FILEGATOR'IN İKİ TUZAĞI (7.15.1'de doğrulandı)
     chmod(event, item) : item.name'i KOŞULSUZ okur. Öğe verilmeden
                          çağrılırsa TypeError ile çöker.
     rename(event, item): istem kutusu seçimi tolere eder ama onaylandığında
                          `from: item.name` der. Öğesiz çağrıda sessizce
                          hata verir.
     Bu yüzden ikisi de YALNIZCA tek öğe seçiliyken etkinleşir ve öğe
     açıkça geçirilir. Diğerleri (copy/move/zip/remove) öğesiz çağrıda
     getSelected()'a düşer, onlarda sorun yok.

   BOZULURSA NE OLUR?
     FileGator sürümü değişip iç yapı kaybolursa çubuklar ve ağaç ÇİZİLMEZ
     ama sol menü ve dosya yöneticisinin kendisi çalışmaya devam eder
     (bkz. baglan()). Tek bir metot kaybolursa yalnızca o düğme pasif
     kalır, diğerleri çalışır (bkz. calisir()).
   ========================================================================== */
(function () {
	"use strict";

	if (window.__wdFmYuklendi) {
		return;
	}
	window.__wdFmYuklendi = true;

	var WDFM_I18N = window.WDFM_I18N || {};
	function wdt(s) {
		return (WDFM_I18N && WDFM_I18N[s]) || s;
	}

	/* --- Sol menü öğeleri -----------------------------------------------
	   Yalnızca HER kullanıcıda bulunan bölümler listelenir. Yönetici-özel
	   sayfalar (Kullanıcılar, Sunucu Ayarları, Günlükler) bilerek yok:
	   müşteriye açılmayan bir bağlantı göstermek kötü bir deneyimdir ve
	   dosya yöneticisi tarafında rolü güvenilir biçimde bilemiyoruz. */
	var MENU = [
		[wdt("Tools"), "TOOLS", "grid", "/list/tools/"],
		[wdt("Health Center"), "HEALTH", "pulse", "/list/health/"],
		[wdt("Disk Usage"), "DISK", "disk", "/list/disk/"],
		[wdt("Domains"), "WEB", "globe", "/list/web/"],
		[wdt("DNS Zones"), "DNS", "book", "/list/dns/"],
		[wdt("Email"), "MAIL", "mail", "/list/mail/"],
		[wdt("Databases"), "DB", "db", "/list/db/"],
		[wdt("CRON Jobs"), "CRON", "clock", "/list/cron/"],
		[wdt("Backups"), "BACKUP", "zip", "/list/backup/"],
		[wdt("File Manager"), "FILE", "folder", "/fm/", true],
		[wdt("Statistics"), "STATS", "chart", "/list/stats/"],
	];

	/* Satır ikonları. Font Awesome dosya yöneticisinde var ama ikon adları
	   sürümle değişebiliyor; kendi setimiz bağımsız kalsın. Değerler tam
	   SVG iç işaretlemesidir (çok parçalı ikonlar için gerekli). */
	var IKON = {
		grid: '<path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/>',
		pulse: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
		disk: '<path d="M22 12H2M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
		globe: '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
		book: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
		mail: '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zM22 6l-10 7L2 6"/>',
		db: '<path d="M12 2c4.42 0 8 1.34 8 3s-3.58 3-8 3-8-1.34-8-3 3.58-3 8-3zM4 5v14c0 1.66 3.58 3 8 3s8-1.34 8-3V5M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/>',
		clock: '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM12 6v6l4 2"/>',
		zip: '<path d="M21 8v13H3V8M1 3h22v5H1zM10 12h4"/>',
		folder: '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
		chart: '<path d="M3 3v18h18M18 9l-5 5-3-3-4 4"/>',
		home: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
		up: '<path d="M12 19V5M5 12l7-7 7 7"/>',

		/* --- araç çubuğu --- */
		fileplus: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M12 18v-6M9 15h6"/>',
		folderplus: '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><path d="M12 11v6M9 14h6"/>',
		upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
		download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
		copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
		move: '<path d="M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20"/>',
		trash: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
		rename: '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
		edit: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/>',
		lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
		archive: '<rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8M10 12h4"/>',
		refresh: '<path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.9-3.4L23 10M1 14l4.6 4.4A9 9 0 0 0 20.5 15"/>',
		search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
		sitemap: '<rect x="3" y="3" width="6" height="4" rx="1"/><rect x="15" y="3" width="6" height="4" rx="1"/><rect x="9" y="17" width="6" height="4" rx="1"/><path d="M6 7v3h12V7M12 10v7"/>',

		/* --- dosya türü --- */
		file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/>',
		image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
		code: '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>',
		log: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
	};

	function svg(ad, boyut) {
		var b = boyut || 14;
		return (
			'<svg width="' + b + '" height="' + b +
			'" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
			'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" ' +
			'aria-hidden="true">' + (IKON[ad] || IKON.grid) + "</svg>"
		);
	}

	function el(etiket, sinif, html) {
		var d = document.createElement(etiket);
		if (sinif) {
			d.className = sinif;
		}
		if (html !== undefined) {
			d.innerHTML = html;
		}
		return d;
	}

	/* ---------------------------------------------------------------- menü */
	function menuCiz() {
		var yan = el("aside", "wdfm-side");

		var marka = el("div", "wdfm-brand");
		// "Hestia" ve "CP" AYNI satırda kalmalı: .wdfm-brand-text bir dikey
		// flex kabıdır, ikisi doğrudan çocuk olsaydı alt alta düşerdi.
		marka.innerHTML =
			'<span class="wdfm-mark">H</span>' +
			'<span class="wdfm-brand-text">' +
			'<b>Hestia<span>CP</span></b>' +
			"<small>" + wdt("CONTROL PANEL") + "</small></span>";
		marka.setAttribute("role", "link");
		marka.setAttribute("tabindex", "0");
		marka.title = wdt("Back to control panel");
		function anaSayfa() {
			window.location.href = "/list/tools/";
		}
		marka.addEventListener("click", anaSayfa);
		marka.addEventListener("keydown", function (e) {
			if (e.key === "Enter" || e.key === " ") {
				e.preventDefault();
				anaSayfa();
			}
		});
		yan.appendChild(marka);

		var liste = el("nav", "wdfm-nav");
		liste.setAttribute("aria-label", wdt("Panel menu"));
		MENU.forEach(function (m) {
			var a = document.createElement("a");
			a.className = "wdfm-nav-item" + (m[4] ? " aktif" : "");
			a.href = m[3];
			a.title = m[0];
			if (m[4]) {
				a.setAttribute("aria-current", "page");
			}
			a.innerHTML =
				'<span class="wdfm-nav-ico">' + svg(m[2]) + "</span>" +
				'<span class="wdfm-nav-label">' + m[0] + "</span>";
			liste.appendChild(a);
		});
		yan.appendChild(liste);

		// --- Künye: panel sidebar'ındakiyle aynı ---
		var kunye = el("div", "wdfm-credit");
		[
			[wdt("Theme & modules"), "webdanismani.com", "https://webdanismani.com"],
			[wdt("Support & forum"), "oblifex.com", "https://oblifex.com"],
		].forEach(function (k) {
			var a = document.createElement("a");
			a.href = k[2];
			a.target = "_blank";
			a.rel = "noopener noreferrer";
			var s1 = el("span", "wdfm-credit-k");
			s1.textContent = k[0];
			var s2 = el("span", "wdfm-credit-v");
			s2.textContent = k[1];
			a.appendChild(s1);
			a.appendChild(s2);
			kunye.appendChild(a);
		});
		yan.appendChild(kunye);

		return yan;
	}

	/* ------------------------------------------------------------ FileGator */
	function kokBul() {
		var hepsi = document.querySelectorAll("*");
		for (var i = 0; i < hepsi.length; i++) {
			if (hepsi[i].__vue__) {
				return hepsi[i].__vue__.$root;
			}
		}
		return null;
	}

	function browserBul(v, derinlik) {
		if (!v || derinlik > 8) {
			return null;
		}
		if (typeof v.goTo === "function") {
			return v;
		}
		var ch = v.$children || [];
		for (var i = 0; i < ch.length; i++) {
			var r = browserBul(ch[i], derinlik + 1);
			if (r) {
				return r;
			}
		}
		return null;
	}

	/* Metot gerçekten var mı? FileGator sürüm atlarsa tek bir düğme pasif
	   kalsın, çubuğun tamamı çökmesin. */
	function calisir(browser, ad) {
		return browser && typeof browser[ad] === "function";
	}

	/* İzin denetimi uygulamanın kendi can() metoduna bırakılır. Metot yoksa
	   (sürüm değişikliği) engellemiyoruz: düğme görünür kalır, yetkisiz
	   çağrıyı sunucu zaten reddeder. */
	function izinli(browser, izin) {
		if (!calisir(browser, "can")) {
			return true;
		}
		try {
			return !!browser.can(izin);
		} catch (e) {
			return true;
		}
	}

	/* ---------------------------------------------------------------- ağaç */
	function agacCiz(kok, browser) {
		var pane = el("aside", "wdfm-tree");
		pane.setAttribute("aria-label", wdt("Folder tree"));

		var bas = el("div", "wdfm-tree-head", wdt("FOLDERS"));
		pane.appendChild(bas);

		var govde = el("div", "wdfm-tree-body");
		pane.appendChild(govde);

		function git(yol) {
			try {
				browser.goTo(yol);
			} catch (e) {
				/* uygulama kendi hatasını gösterir */
			}
		}

		function satir(ad, yol, derinlik, tur) {
			var a = document.createElement("a");
			a.className = "wdfm-tree-item" + (tur ? " " + tur : "");
			a.href = "#";
			a.style.paddingLeft = 8 + derinlik * 12 + "px";
			a.title = yol;
			var ikon = yol === "/" ? "home" : "folder";
			a.innerHTML =
				'<span class="wdfm-tree-ico">' + svg(ikon, 13) + "</span>" +
				'<span class="wdfm-tree-ad"></span>';
			a.querySelector(".wdfm-tree-ad").textContent = ad;
			a.addEventListener("click", function (e) {
				e.preventDefault();
				git(yol);
			});
			return a;
		}

		function yenile() {
			var cwd = kok.$store.state.cwd;
			var konum = cwd.location || "/";
			govde.innerHTML = "";

			// Ana dizin + üst klasör zinciri
			var parcalar = konum.split("/").filter(Boolean);
			govde.appendChild(satir(wdt("Home directory"), "/", 0, konum === "/" ? "aktif" : ""));

			var birikim = "";
			parcalar.forEach(function (p, i) {
				birikim += "/" + p;
				var sonMu = i === parcalar.length - 1;
				govde.appendChild(satir(p, birikim, i + 1, sonMu ? "aktif" : ""));
			});

			// Bulunulan dizinin alt klasörleri
			var alt = (cwd.content || []).filter(function (o) {
				return o.type === "dir";
			});
			alt.forEach(function (o) {
				govde.appendChild(satir(o.name, o.path, parcalar.length + 1, "alt"));
			});

			if (!alt.length && konum !== "/") {
				govde.appendChild(el("div", "wdfm-tree-bos", wdt("No subfolders")));
			}
		}

		yenile();
		// Store değiştikçe ağaç tazelenir. `watch` bir durdurma işlevi döner;
		// sayfa ömrü boyunca açık kalması istendiği için saklanmıyor.
		kok.$store.watch(
			function (s) {
				return s.cwd.location + "|" + (s.cwd.content || []).length;
			},
			yenile
		);

		return pane;
	}

	/* ------------------------------------------------------- araç çubuğu */

	/* kip: hangi seçim durumunda etkin?
	     hep   — her zaman
	     coklu — en az bir öğe seçili
	     tekli — tam olarak bir öğe seçili
	   izin: FileGator'ın can() değeri. */
	var ARACLAR = [
		{ ad: wdt("File"), ikon: "fileplus", kip: "hep", izin: ["read", "write"], is: "yeniDosya" },
		{ ad: wdt("Folder"), ikon: "folderplus", kip: "hep", izin: ["read", "write"], is: "yeniKlasor" },
		{ ad: wdt("Upload"), ikon: "upload", kip: "hep", izin: "upload", is: "yukle", vurgu: true },
		{ ad: wdt("Copy"), ikon: "copy", kip: "coklu", izin: "write", is: "kopyala" },
		{ ad: wdt("Move"), ikon: "move", kip: "coklu", izin: "write", is: "tasi" },
		{ ad: wdt("Download"), ikon: "download", kip: "coklu", izin: "batchdownload", is: "indir" },
		{ ad: wdt("Delete"), ikon: "trash", kip: "coklu", izin: "write", is: "sil" },
		{ ad: wdt("Rename"), ikon: "rename", kip: "tekli", izin: "write", is: "adDegistir" },
		{ ad: wdt("Edit"), ikon: "edit", kip: "tekli", izin: ["download"], is: "duzenle" },
		{ ad: wdt("Permissions"), ikon: "lock", kip: "tekli", izin: ["write", "chmod"], is: "izinler" },
		{ ad: wdt("Compress"), ikon: "archive", kip: "coklu", izin: ["write", "zip"], is: "sikistir" },
	];

	/* Her eylem FileGator'ın KENDİ metodunu çağırır; kendi API çağrımızı
	   yapmıyoruz. chmod/rename öğeyi açıkça alır (bkz. dosya başlığı). */
	var ISLER = {
		yeniDosya: function (b) {
			b.create("file");
		},
		yeniKlasor: function (b) {
			b.create("dir");
		},
		yukle: function (b) {
			// FileGator'ın yükleme girdisi `v-if="... && ! checked.length"`
			// ile çizilir: seçim varken DOM'da yoktur. Önce seçimi temizle,
			// Vue yeniden çizsin, sonra tıkla.
			function tikla() {
				var inp = document.querySelector(
					"#multi-actions input[type='file'], #browser input[type='file']"
				);
				if (inp) {
					inp.click();
				}
			}
			if (b.checked && b.checked.length) {
				b.checked = [];
				if (b.$nextTick) {
					b.$nextTick(tikla);
					return;
				}
			}
			tikla();
		},
		kopyala: function (b, e) {
			b.copy(e);
		},
		tasi: function (b, e) {
			b.move(e);
		},
		indir: function (b) {
			b.batchDownload();
		},
		sil: function (b, e) {
			b.remove(e);
		},
		adDegistir: function (b, e) {
			b.rename(e, b.checked[0]);
		},
		duzenle: function (b) {
			var oge = b.checked[0];
			if (!oge) {
				return;
			}
			// preview() modal bileşenini uzantıdan seçer; önizlenemeyen bir
			// dosyada modal null kalır ve boş kutu açılır. Önce sor.
			if (calisir(b, "hasPreview") && !b.hasPreview(oge.path)) {
				if (calisir(b, "download")) {
					b.download(oge);
				}
				return;
			}
			b.preview(oge);
		},
		izinler: function (b, e) {
			b.chmod(e, b.checked[0]);
		},
		sikistir: function (b, e) {
			b.zip(e);
		},
	};

	/* Eylemin dayandığı metot(lar) gerçekten var mı? */
	var GEREKEN = {
		yeniDosya: ["create"],
		yeniKlasor: ["create"],
		yukle: [],
		kopyala: ["copy"],
		tasi: ["move"],
		indir: ["batchDownload"],
		sil: ["remove"],
		adDegistir: ["rename"],
		duzenle: ["preview"],
		izinler: ["chmod"],
		sikistir: ["zip"],
	};

	function barCiz(kok, browser) {
		var bar = el("div", "wdfm-bar");
		bar.id = "wdfm-bar";

		/* --- 1. satır: eylemler --- */
		var arac = el("div", "wdfm-bar-araclar");
		var dugmeler = [];

		ARACLAR.forEach(function (t) {
			var gerek = GEREKEN[t.is] || [];
			var eksik = gerek.some(function (m) {
				return !calisir(browser, m);
			});
			if (eksik || !izinli(browser, t.izin)) {
				return; // yetki yok ya da metot kayıp: düğmeyi hiç çizme
			}

			var a = document.createElement("button");
			a.type = "button";
			a.className = "wdfm-btn" + (t.vurgu ? " vurgu" : "");
			a.title = t.ad;
			a.innerHTML =
				'<span class="wdfm-btn-ico">' + svg(t.ikon, 13) + "</span>" +
				'<span class="wdfm-btn-ad"></span>';
			a.querySelector(".wdfm-btn-ad").textContent = t.ad;
			a.addEventListener("click", function (e) {
				e.preventDefault();
				if (a.disabled) {
					return;
				}
				try {
					ISLER[t.is](browser, e);
				} catch (hata) {
					// Uygulamanın kendi hata yönetimi devrede değilse sessiz
					// kalmak yerine konsola yaz; kullanıcıyı kilitleme.
					if (window.console) {
						console.error("wd-fm: " + t.is + " başarısız", hata);
					}
				}
			});
			arac.appendChild(a);
			dugmeler.push({ dug: a, kip: t.kip });
		});

		bar.appendChild(arac);

		/* --- 2. satır: gezinme --- */
		var gez = el("div", "wdfm-bar-gezinme");

		function ikonDugme(ikon, baslik, isle) {
			var b = document.createElement("button");
			b.type = "button";
			b.className = "wdfm-ico-btn";
			b.title = baslik;
			b.setAttribute("aria-label", baslik);
			b.innerHTML = svg(ikon, 13);
			b.addEventListener("click", function (e) {
				e.preventDefault();
				isle();
			});
			return b;
		}

		function konum() {
			return (kok.$store.state.cwd.location || "/");
		}

		gez.appendChild(
			ikonDugme("up", wdt("Parent directory"), function () {
				var k = konum();
				if (k === "/") {
					return;
				}
				var ust = k.split("/").slice(0, -1).join("/") || "/";
				browser.goTo(ust);
			})
		);
		gez.appendChild(
			ikonDugme("home", wdt("Home directory"), function () {
				browser.goTo("/");
			})
		);

		var yol = el("div", "wdfm-yol");
		yol.setAttribute("aria-label", wdt("Current path"));
		gez.appendChild(yol);

		// Stok gezinme satırı gizleniyor (bkz. wd-fm.css); oradaki arama ve
		// klasör-seç düğmeleri işlev kaybı olmasın diye buraya taşınır.
		if (calisir(browser, "search")) {
			gez.appendChild(
				ikonDugme("search", wdt("Search files"), function () {
					browser.search();
				})
			);
		}
		if (calisir(browser, "selectDir")) {
			gez.appendChild(
				ikonDugme("sitemap", wdt("Select in folder tree"), function () {
					browser.selectDir();
				})
			);
		}

		if (calisir(browser, "loadFiles")) {
			var yenileDug = ikonDugme("refresh", wdt("Refresh"), function () {
				browser.loadFiles();
			});
			yenileDug.className = "wdfm-ico-btn wdfm-yenile";
			yenileDug.innerHTML = svg("refresh", 13) + "<span>" + wdt("Refresh") + "</span>";
			gez.appendChild(yenileDug);
		}

		bar.appendChild(gez);

		/* --- yol çubuğunu doldur --- */
		function yolCiz() {
			var k = konum();
			yol.innerHTML = "";
			var parcalar = k.split("/").filter(Boolean);

			function parca(ad, hedef, sonMu) {
				var a = document.createElement("a");
				a.href = "#";
				a.className = "wdfm-yol-parca" + (sonMu ? " son" : "");
				a.textContent = ad;
				a.addEventListener("click", function (e) {
					e.preventDefault();
					browser.goTo(hedef);
				});
				yol.appendChild(a);
				if (!sonMu) {
					yol.appendChild(el("span", "wdfm-yol-ayrac", "/"));
				}
			}

			parca("~", "/", parcalar.length === 0);
			var birikim = "";
			parcalar.forEach(function (p, i) {
				birikim += "/" + p;
				parca(p, birikim, i === parcalar.length - 1);
			});
		}

		/* --- seçime göre düğmeleri etkinleştir --- */
		function dugmeleriGuncelle() {
			var n = (browser.checked || []).length;
			dugmeler.forEach(function (d) {
				var acik =
					d.kip === "hep" ||
					(d.kip === "coklu" && n >= 1) ||
					(d.kip === "tekli" && n === 1);
				d.dug.disabled = !acik;
				d.dug.classList.toggle("pasif", !acik);
			});
		}

		bar.__yenile = function () {
			yolCiz();
			dugmeleriGuncelle();
		};
		bar.__yenile();

		return bar;
	}

	/* -------------------------------------------------------- durum çubuğu */
	function durumCiz(kok, browser) {
		var d = el("div", "wdfm-durum");
		d.id = "wdfm-durum";

		var sol = el("span", "wdfm-durum-sol");
		var sag = el("span", "wdfm-durum-sag");
		d.appendChild(sol);
		d.appendChild(el("span", "wdfm-durum-bosluk"));
		d.appendChild(sag);

		d.__yenile = function () {
			var icerik = kok.$store.state.cwd.content || [];
			// "back" satırı (üst dizine dön) gerçek bir öğe değil, sayma.
			var gercek = icerik.filter(function (o) {
				return o.type !== "back";
			});
			var klasor = gercek.filter(function (o) {
				return o.type === "dir";
			}).length;
			var dosya = gercek.length - klasor;
			var secili = (browser.checked || []).length;

			var metin = klasor + " " + wdt("folders") + ", " + dosya + " " + wdt("files");
			if (secili) {
				metin += " · " + secili + " " + wdt("selected");
			}
			sol.textContent = metin;
			sag.textContent = kok.$store.state.cwd.location || "/";
		};
		d.__yenile();

		return d;
	}

	/* ------------------------------------------------- dosya türü ikonları */
	var TURLER = [
		[/\.(jpe?g|png|gif|svg|webp|bmp|ico|avif)$/i, "image", "gorsel"],
		[/\.(php|js|mjs|css|s[ac]ss|html?|sh|json|xml|ya?ml|sql|py|rb|ts)$/i, "code", "kod"],
		[/\.(zip|tar|gz|tgz|bz2|xz|rar|7z)$/i, "archive", "arsiv"],
		[/\.(log|bytes)$/i, "log", "gunluk"],
	];

	function turBul(ad) {
		for (var i = 0; i < TURLER.length; i++) {
			if (TURLER[i][0].test(ad)) {
				return { ikon: TURLER[i][1], sinif: TURLER[i][2] };
			}
		}
		return { ikon: "file", sinif: "dosya" };
	}

	/* Tabloda ad hücresinin başına türe göre renkli ikon koyar. Buefy tabloyu
	   sıralama/sayfalama sonrası yeniden çizer; gözlemci bu işlevi yeniden
	   çağırır, bu yüzden zaten işlenmiş satırlar atlanır. */
	function turIkonlari() {
		var satirlar = document.querySelectorAll("#browser tbody tr.file-row");
		for (var i = 0; i < satirlar.length; i++) {
			var tr = satirlar[i];
			var bag = tr.querySelector("a.name");
			if (!bag || bag.querySelector(".wdfm-tur-ico")) {
				continue;
			}
			var tur;
			if (tr.classList.contains("type-dir")) {
				tur = { ikon: "folder", sinif: "klasor" };
			} else if (tr.classList.contains("type-back")) {
				tur = { ikon: "up", sinif: "geri" };
			} else {
				tur = turBul(bag.textContent.trim());
			}
			var s = el("span", "wdfm-tur-ico " + tur.sinif, svg(tur.ikon, 14));
			bag.insertBefore(s, bag.firstChild);
			bag.classList.add("wdfm-adli");
		}
	}

	/* --------------------------------------------------------------- kurulum */
	function cubuklariBagla(kok, browser) {
		var bar = barCiz(kok, browser);
		var durum = durumCiz(kok, browser);
		var mesgul = false;

		function yerlestir() {
			var b = document.getElementById("browser");
			if (!b) {
				return;
			}
			mesgul = true;
			if (!document.getElementById("wdfm-bar")) {
				b.insertBefore(bar, b.firstChild);
			}
			if (!document.getElementById("wdfm-durum")) {
				b.appendChild(durum);
			}
			turIkonlari();
			mesgul = false;
		}

		function tazele() {
			bar.__yenile();
			durum.__yenile();
			yerlestir();
		}

		yerlestir();

		// Vue yeniden çizerken enjekte ettiğimiz düğümleri silebilir; geri koy.
		// mesgul bayrağı kendi eklemelerimizin gözlemciyi tetiklemesini keser.
		var hedef = document.getElementById("app") || document.body;
		var gozlemci = new MutationObserver(function () {
			if (mesgul) {
				return;
			}
			yerlestir();
		});
		gozlemci.observe(hedef, { childList: true, subtree: true });

		// Dizin veya içerik değişince yol, sayaçlar ve ikonlar tazelensin.
		kok.$store.watch(
			function (s) {
				return s.cwd.location + "|" + (s.cwd.content || []).length;
			},
			tazele
		);

		// Seçim değişince düğmelerin etkinliği ve "n seçili" güncellensin.
		if (typeof browser.$watch === "function") {
			browser.$watch(
				"checked",
				function () {
					bar.__yenile();
					durum.__yenile();
				},
				{ deep: true }
			);
		}

		document.body.classList.add("wdfm-barli");
	}

	function baglan(deneme) {
		var kok = kokBul();
		var browser = kok ? browserBul(kok, 0) : null;

		if (!kok || !browser || !kok.$store) {
			if (deneme < 40) {
				return setTimeout(function () {
					baglan(deneme + 1);
				}, 250);
			}
			// FileGator iç yapısı değişmiş: ağaç ve çubuklar olmadan devam.
			// Sol menü ve dosya yöneticisi çalışmaya devam eder.
			document.body.classList.add("wdfm-agacsiz");
			return;
		}

		document.body.appendChild(agacCiz(kok, browser));
		document.body.classList.add("wdfm-agacli");

		try {
			cubuklariBagla(kok, browser);
		} catch (e) {
			// Çubuk çizilemese bile ağaç ve menü ayakta kalsın.
			if (window.console) {
				console.error("wd-fm: araç çubuğu kurulamadı", e);
			}
		}
	}

	/* Sabit yan paneller içeriğin üstünü örtmesin diye gövde sağa kaydırılır.
	   Kaydırmayı CSS'te sabit bir kimliğe bağlamak güvenilir DEĞİL: FileGator
	   yalnızca <div id="app"> üretir, HestiaCP sürümleri ise sarmalayıcıyı
	   değiştirebilir. Bu yüzden gövde çalışma anında bulunur ve işaretlenir;
	   CSS tek bir sınıfı hedefler. İki aday da varsa yalnızca dıştaki
	   işaretlenir — ikisi birden kaydırılsaydı boşluk iki katına çıkardı. */
	function govdeIsaretle() {
		var g = document.getElementById("wrapper") || document.getElementById("app");
		if (g) {
			g.classList.add("wdfm-govde");
		}
		return !!g;
	}

	function baslat() {
		document.body.classList.add("wdfm-kabuk");
		document.body.appendChild(menuCiz());
		if (!govdeIsaretle()) {
			// Gövde bulunamadıysa kaydırma yapılamaz; panelleri sabitlemek
			// içeriği örterdi. Akışa geçir, düzen bozulmasın.
			document.body.classList.add("wdfm-govdesiz");
		}
		baglan(0);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", baslat);
	} else {
		baslat();
	}
})();
