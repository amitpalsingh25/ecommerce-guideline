// Media library + reusable image picker. Plain JS, no build step.
(function () {
  "use strict";
  var CFG = window.FSA || {};

  var COPY_ICON = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
  var CHECK_ICON = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
  function copyFeedback(btn) {
    var old = btn.getAttribute("data-icon") || btn.innerHTML;
    btn.setAttribute("data-icon", old);
    btn.innerHTML = CHECK_ICON; btn.classList.add("copied");
    setTimeout(function () { btn.innerHTML = old; btn.classList.remove("copied"); }, 1200);
  }
  function copyUrl(url, btn) { if (url && navigator.clipboard) { navigator.clipboard.writeText(url); copyFeedback(btn); } }

  function el(tag, cls, html) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (html != null) e.innerHTML = html;
    return e;
  }

  function fetchList() {
    return fetch(CFG.mediaList, { credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (j) { return (j && j.files) || []; })
      .catch(function () { return []; });
  }

  // Downscale to <=1000px wide + re-encode to WebP (high quality, small size).
  // Keeps transparency; skips gif/svg/avif; never upscales; falls back to original if it can't beat it.
  var MAX_W = 1000, QUALITY = 0.85;
  function compressImage(file) {
    return new Promise(function (resolve) {
      var t = file.type || "";
      if (t.indexOf("image/") !== 0 || t === "image/gif" || t === "image/svg+xml" || t === "image/avif") {
        resolve({ blob: file, name: file.name }); return;
      }
      var img = new Image(), u = URL.createObjectURL(file);
      img.onload = function () {
        var ow = img.naturalWidth, oh = img.naturalHeight, w = ow, h = oh;
        if (w > MAX_W) { h = Math.round(h * MAX_W / w); w = MAX_W; }
        var c = document.createElement("canvas");
        c.width = w; c.height = h;
        c.getContext("2d").drawImage(img, 0, 0, w, h);
        URL.revokeObjectURL(u);
        var base = (file.name || "image").replace(/\.[^.]+$/, "");
        c.toBlob(function (blob) {
          if (blob && blob.size > 0 && (blob.size < file.size || w < ow)) resolve({ blob: blob, name: base + ".webp" });
          else resolve({ blob: file, name: file.name });
        }, "image/webp", QUALITY);
      };
      img.onerror = function () { URL.revokeObjectURL(u); resolve({ blob: file, name: file.name }); };
      img.src = u;
    });
  }

  function uploadFile(file) {
    return compressImage(file).then(function (out) {
      var fd = new FormData();
      fd.append("file", out.blob, out.name);
      return fetch(CFG.upload, { method: "POST", body: fd, credentials: "same-origin" })
        .then(function (r) { return r.json(); });
    });
  }

  function deleteFile(name) {
    var fd = new FormData();
    fd.append("name", name);
    fd.append("_csrf", CFG.csrf || "");
    return fetch(CFG.mediaDelete, { method: "POST", body: fd, credentials: "same-origin" })
      .then(function (r) { return r.json(); });
  }

  // ---------- Lightbox (view a single image) ----------
  var lb, lbImg, lbName, lbStatus, lbOnDelete;

  function buildLightbox() {
    lb = el("div", "media-lightbox");
    var box = el("div", "media-lightbox-box");
    var close = el("button", "media-lightbox-close", "&times;");
    close.type = "button";
    lbImg = el("img", "media-lightbox-img");
    lbName = el("div", "media-lightbox-name");
    var actions = el("div", "media-lightbox-actions");
    var copy = el("button", "btn btn-ghost", "Copy link");
    copy.type = "button";
    var del = el("button", "btn btn-danger", "Delete");
    del.type = "button";
    actions.appendChild(copy); actions.appendChild(del);
    lbStatus = el("div", "media-lightbox-status");
    box.appendChild(close); box.appendChild(lbImg); box.appendChild(lbName); box.appendChild(actions); box.appendChild(lbStatus);
    lb.appendChild(box);
    document.body.appendChild(lb);

    lb.addEventListener("click", function (e) { if (e.target === lb) hideLightbox(); });
    close.addEventListener("click", hideLightbox);
    copy.addEventListener("click", function () {
      if (navigator.clipboard) navigator.clipboard.writeText(lbImg.src);
      lbStatus.textContent = "Link copied: " + lbImg.src;
    });
    del.addEventListener("click", function () {
      if (!lbOnDelete) return;
      if (!confirm("Delete this image? Products using it will lose the photo.")) return;
      lbOnDelete();
    });
  }

  function openLightbox(url, name, onDelete) {
    if (!lb) buildLightbox();
    lbImg.src = url;
    lbName.textContent = name || "";
    lbStatus.textContent = "";
    lbOnDelete = onDelete || null;
    lb.classList.add("open");
  }
  function hideLightbox() { if (lb) lb.classList.remove("open"); }

  // ---------- Picker modal (WordPress-style) ----------
  var overlay, gridEl, statusEl, searchEl, dropEl, currentCb;
  var selUrl = "", selName = "", selTile = null;
  // side panel refs
  var sPrev, sName, sDims, sUrl, sUse, sDel;

  function buildModal() {
    overlay = el("div", "media-modal-overlay");
    var modal = el("div", "media-modal");

    // head
    var head = el("div", "media-modal-head");
    head.appendChild(el("strong", null, "Select or upload image"));
    searchEl = el("input", "media-modal-search");
    searchEl.type = "search"; searchEl.placeholder = "Search…";
    var up = el("label", "btn btn-flame media-modal-up", "Upload");
    var upInput = el("input");
    upInput.type = "file"; upInput.accept = "image/*"; upInput.multiple = true; upInput.style.display = "none";
    up.appendChild(upInput);
    var close = el("button", "media-modal-close", "&times;");
    close.type = "button";
    head.appendChild(searchEl); head.appendChild(up); head.appendChild(close);

    // body: main (dropzone + grid) + side
    var body = el("div", "media-modal-body");
    var main = el("div", "media-modal-main");
    dropEl = el("div", "media-dropzone", "Drag images here or use Upload");
    gridEl = el("div", "media-modal-grid");
    main.appendChild(dropEl); main.appendChild(gridEl);

    var side = el("div", "media-modal-side");
    side.innerHTML =
      '<div class="ms-prevwrap"><img class="ms-prev" alt=""></div>'
      + '<div class="ms-empty">No image selected.</div>'
      + '<div class="ms-name"></div>'
      + '<div class="ms-dims"></div>'
      + '<label class="ms-lbl">URL</label><div class="ms-urlrow"><input class="ms-url" readonly onfocus="this.select()"><button type="button" class="icon-copy ms-copy" title="Copy URL">' + COPY_ICON + '</button></div>'
      + '<button type="button" class="btn btn-danger ms-del">Delete permanently</button>';
    body.appendChild(main); body.appendChild(side);

    // foot
    var foot = el("div", "media-modal-foot");
    statusEl = el("div", "media-modal-status");
    var cancel = el("button", "btn btn-ghost", "Cancel");
    cancel.type = "button";
    sUse = el("button", "btn btn-flame media-use", "Use this image");
    sUse.type = "button"; sUse.disabled = true;
    foot.appendChild(statusEl); foot.appendChild(cancel); foot.appendChild(sUse);

    modal.appendChild(head); modal.appendChild(body); modal.appendChild(foot);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // side refs
    sPrev = side.querySelector(".ms-prev");
    sName = side.querySelector(".ms-name");
    sDims = side.querySelector(".ms-dims");
    sUrl = side.querySelector(".ms-url");
    sDel = side.querySelector(".ms-del");

    // events
    overlay.addEventListener("click", function (e) { if (e.target === overlay) hide(); });
    close.addEventListener("click", hide);
    cancel.addEventListener("click", hide);
    sUse.addEventListener("click", function () { if (selUrl) pick(selUrl); });
    side.querySelector(".ms-copy").addEventListener("click", function () { copyUrl(selUrl, this); });
    sDel.addEventListener("click", function () {
      if (!selName || !confirm("Delete this image permanently? Products using it lose the photo.")) return;
      deleteFile(selName).then(function (j) {
        if (j && j.ok) { if (selTile) selTile.remove(); clearSelection(); statusEl.textContent = "Deleted"; }
        else statusEl.textContent = (j && j.error) || "Delete failed";
      });
    });
    searchEl.addEventListener("input", applyFilter);
    upInput.addEventListener("change", function () { handleUploads(Array.prototype.slice.call(upInput.files || [])); upInput.value = ""; });

    // drag & drop
    ["dragenter", "dragover"].forEach(function (ev) {
      main.addEventListener(ev, function (e) { e.preventDefault(); dropEl.classList.add("over"); });
    });
    ["dragleave", "drop"].forEach(function (ev) {
      main.addEventListener(ev, function (e) { e.preventDefault(); dropEl.classList.remove("over"); });
    });
    main.addEventListener("drop", function (e) {
      var files = e.dataTransfer && e.dataTransfer.files ? Array.prototype.slice.call(e.dataTransfer.files) : [];
      if (files.length) handleUploads(files);
    });
  }

  function handleUploads(files) {
    files = files.filter(function (f) { return /^image\//.test(f.type); });
    if (!files.length) return;
    statusEl.textContent = "Uploading " + files.length + "…";
    var done = 0, last = null;
    files.forEach(function (f) {
      uploadFile(f).then(function (j) {
        done++;
        if (j && j.url) { var t = makeTile(j.url, j.url.split("/").pop()); if (gridEl.firstChild) gridEl.insertBefore(t, gridEl.firstChild); else gridEl.appendChild(t); last = t; }
        if (done === files.length) { statusEl.textContent = "Uploaded"; if (last) last.click(); }
      });
    });
  }

  function makeTile(url, name) {
    var t = el("button", "media-pick-tile");
    t.type = "button"; t.setAttribute("data-url", url); t.setAttribute("data-name", name);
    t.style.backgroundImage = "url('" + url + "')";
    t.innerHTML = '<span class="media-pick-check">&#10003;</span>';
    t.addEventListener("click", function () { selectTile(t, url, name); });
    t.addEventListener("dblclick", function () { pick(url); });
    return t;
  }

  function renderGrid(files) {
    gridEl.innerHTML = "";
    if (!files.length) { gridEl.appendChild(el("p", "media-empty", "No images yet — drag in or Upload.")); return; }
    files.forEach(function (f) { gridEl.appendChild(makeTile(f.url, f.name)); });
    applyFilter();
  }

  function applyFilter() {
    var q = (searchEl.value || "").toLowerCase();
    Array.prototype.forEach.call(gridEl.querySelectorAll(".media-pick-tile"), function (t) {
      t.style.display = (t.getAttribute("data-name") || "").toLowerCase().indexOf(q) === -1 ? "none" : "";
    });
  }

  function selectTile(tile, url, name) {
    if (selTile) selTile.classList.remove("selected");
    selTile = tile; selUrl = url; selName = name;
    tile.classList.add("selected");
    overlay.querySelector(".media-modal-side").classList.add("has-sel");
    sPrev.src = url; sName.textContent = name; sUrl.value = url; sDims.textContent = "";
    sUse.disabled = false;
    sPrev.onload = function () { sDims.textContent = sPrev.naturalWidth + " × " + sPrev.naturalHeight + " px"; };
  }

  function clearSelection() {
    selTile = null; selUrl = ""; selName = "";
    overlay.querySelector(".media-modal-side").classList.remove("has-sel");
    sUse.disabled = true; sUrl.value = ""; sName.textContent = ""; sDims.textContent = "";
  }

  function pick(url) { if (currentCb) currentCb(url); hide(); }
  function hide() { if (overlay) overlay.classList.remove("open"); }

  function open(cb) {
    if (!overlay) buildModal();
    currentCb = cb;
    clearSelection();
    if (searchEl) searchEl.value = "";
    statusEl.textContent = "Loading…";
    overlay.classList.add("open");
    fetchList().then(function (files) { statusEl.textContent = ""; renderGrid(files); });
  }

  window.FSAMedia = { pick: open };

  // ---------- img-field widgets (event delegation) ----------
  function setField(field, url) {
    field.querySelector(".img-val").value = url || "";
    var thumb = field.querySelector(".img-thumb");
    if (thumb) thumb.style.backgroundImage = url ? "url('" + url + "')" : "";
    field.classList.toggle("has-img", !!url);
  }

  document.addEventListener("click", function (e) {
    var pickBtn = e.target.closest && e.target.closest(".img-pick");
    if (pickBtn) {
      e.preventDefault();
      var f1 = pickBtn.closest("[data-img-field]");
      open(function (url) { setField(f1, url); });
      return;
    }
    var clearBtn = e.target.closest && e.target.closest(".img-clear");
    if (clearBtn) {
      e.preventDefault();
      setField(clearBtn.closest("[data-img-field]"), "");
      return;
    }
  });

  // ---------- Attachment details (WordPress-style) ----------
  var att, aImg, aWrap, aInfo, aTitle, aAlt, aUrl, aUsed, aUsedWrap, aStatus, aScaleRow, aScaleW;
  var aName = "", aUrlStr = "", aType = "", aTile = null, cropMode = false, cropBox = null, cropDrag = null;

  function fmtSize(b) { if (!b) return "—"; if (b < 1024) return b + " B"; if (b < 1048576) return Math.round(b / 1024) + " KB"; return (b / 1048576).toFixed(1) + " MB"; }

  function buildAttach() {
    att = el("div", "media-attach-overlay");
    var box = el("div", "media-attach");
    var head = el("div", "media-attach-head");
    head.appendChild(el("strong", null, "Attachment details"));
    var close = el("button", "media-attach-close", "&times;"); close.type = "button";
    head.appendChild(close);

    var body = el("div", "media-attach-body");
    var left = el("div", "ma-left");
    aWrap = el("div", "ma-imgwrap");
    aImg = el("img", "ma-img");
    aWrap.appendChild(aImg);
    left.appendChild(aWrap);
    var tools = el("div", "ma-tools");
    tools.innerHTML =
      '<button type="button" class="btn btn-ghost" data-op="rot-ccw" title="Rotate left">&#8634;</button>'
      + '<button type="button" class="btn btn-ghost" data-op="rot-cw" title="Rotate right">&#8635;</button>'
      + '<button type="button" class="btn btn-ghost" data-op="flip-h" title="Flip horizontal">&#8646;</button>'
      + '<button type="button" class="btn btn-ghost" data-op="flip-v" title="Flip vertical">&#8645;</button>'
      + '<button type="button" class="btn btn-ghost" data-op="scale">Scale</button>'
      + '<button type="button" class="btn btn-ghost" data-op="crop">Crop</button>';
    left.appendChild(tools);
    aScaleRow = el("div", "ma-scalerow");
    aScaleRow.innerHTML = '<span>New width (px)</span>';
    aScaleW = el("input"); aScaleW.type = "number"; aScaleW.min = "16";
    aScaleRow.appendChild(aScaleW);
    var scaleApply = el("button", "btn btn-flame", "Apply"); scaleApply.type = "button";
    var scaleCancel = el("button", "btn btn-ghost", "Cancel"); scaleCancel.type = "button";
    aScaleRow.appendChild(scaleApply); aScaleRow.appendChild(scaleCancel);
    left.appendChild(aScaleRow);
    var cropRow = el("div", "ma-croprow");
    cropRow.appendChild(el("span", "ma-crophint", "Drag a box on the image, then Apply."));
    var cropApply = el("button", "btn btn-flame", "Apply crop"); cropApply.type = "button";
    var cropCancel = el("button", "btn btn-ghost", "Cancel"); cropCancel.type = "button";
    cropRow.appendChild(cropApply); cropRow.appendChild(cropCancel);
    left.appendChild(cropRow);

    var side = el("div", "ma-side");
    side.innerHTML =
      '<dl class="ma-info"></dl>'
      + '<label class="ms-lbl">Title</label><input class="ma-title">'
      + '<label class="ms-lbl">Alternative text</label><textarea class="ma-alt" rows="2"></textarea>'
      + '<label class="ms-lbl">URL</label><div class="ms-urlrow"><input class="ma-url" readonly onfocus="this.select()"><button type="button" class="icon-copy ma-copy" title="Copy URL">' + COPY_ICON + '</button></div>'
      + '<div class="ma-usedwrap"><label class="ms-lbl">Used by</label><div class="ma-used"></div></div>'
      + '<div class="ma-actions"><button type="button" class="btn btn-flame ma-save">Save details</button><button type="button" class="btn btn-danger ma-del2">Delete permanently</button></div>'
      + '<div class="ma-status"></div>';
    body.appendChild(left); body.appendChild(side);
    box.appendChild(head); box.appendChild(body); att.appendChild(box);
    document.body.appendChild(att);

    aInfo = side.querySelector(".ma-info");
    aTitle = side.querySelector(".ma-title");
    aAlt = side.querySelector(".ma-alt");
    aUrl = side.querySelector(".ma-url");
    aUsed = side.querySelector(".ma-used");
    aUsedWrap = side.querySelector(".ma-usedwrap");
    aStatus = side.querySelector(".ma-status");

    att.addEventListener("click", function (e) { if (e.target === att) hideAttach(); });
    close.addEventListener("click", hideAttach);
    side.querySelector(".ma-copy").addEventListener("click", function () { copyUrl(aUrlStr, this); });
    side.querySelector(".ma-save").addEventListener("click", saveDetails);
    side.querySelector(".ma-del2").addEventListener("click", function () {
      if (!confirm("Delete this image permanently? Products using it lose the photo.")) return;
      deleteFile(aName).then(function (j) {
        if (j && j.ok) { if (aTile) aTile.remove(); hideAttach(); } else aStatus.textContent = (j && j.error) || "Delete failed";
      });
    });
    tools.addEventListener("click", function (e) {
      var b = e.target.closest("[data-op]"); if (!b) return;
      var op = b.getAttribute("data-op");
      if (op === "rot-ccw") doEdit({ op: "rotate", dir: "ccw" });
      else if (op === "rot-cw") doEdit({ op: "rotate", dir: "cw" });
      else if (op === "flip-h") doEdit({ op: "flip", mode: "h" });
      else if (op === "flip-v") doEdit({ op: "flip", mode: "v" });
      else if (op === "scale") { aScaleW.value = aImg.naturalWidth; att.classList.add("scaling"); att.classList.remove("cropping"); }
      else if (op === "crop") startCrop();
    });
    scaleApply.addEventListener("click", function () { var w = parseInt(aScaleW.value, 10); if (w >= 16) doEdit({ op: "scale", w: w }).then(function () { att.classList.remove("scaling"); }); });
    scaleCancel.addEventListener("click", function () { att.classList.remove("scaling"); });
    cropApply.addEventListener("click", applyCrop);
    cropCancel.addEventListener("click", endCrop);

    // crop drag
    aWrap.addEventListener("mousedown", function (e) {
      if (!cropMode) return;
      e.preventDefault();
      var r = aImg.getBoundingClientRect();
      cropDrag = { x: e.clientX - r.left, y: e.clientY - r.top, r: r };
      if (!cropBox) { cropBox = el("div", "ma-cropbox"); aWrap.appendChild(cropBox); }
      cropBox.style.cssText = "left:" + cropDrag.x + "px;top:" + cropDrag.y + "px;width:0;height:0";
    });
    document.addEventListener("mousemove", function (e) {
      if (!cropMode || !cropDrag) return;
      var r = cropDrag.r;
      var cx = Math.max(0, Math.min(e.clientX - r.left, r.width));
      var cy = Math.max(0, Math.min(e.clientY - r.top, r.height));
      var x = Math.min(cx, cropDrag.x), y = Math.min(cy, cropDrag.y);
      cropBox.style.cssText = "left:" + x + "px;top:" + y + "px;width:" + Math.abs(cx - cropDrag.x) + "px;height:" + Math.abs(cy - cropDrag.y) + "px";
    });
    document.addEventListener("mouseup", function () { cropDrag = null; });
  }

  function startCrop() { cropMode = true; att.classList.add("cropping"); att.classList.remove("scaling"); }
  function endCrop() { cropMode = false; att.classList.remove("cropping"); if (cropBox) { cropBox.remove(); cropBox = null; } }
  function applyCrop() {
    if (!cropBox) { aStatus.textContent = "Draw a crop box first"; return; }
    var r = aImg.getBoundingClientRect();
    var scale = aImg.naturalWidth / r.width;
    var bx = parseFloat(cropBox.style.left), by = parseFloat(cropBox.style.top);
    var bw = parseFloat(cropBox.style.width), bh = parseFloat(cropBox.style.height);
    if (bw < 6 || bh < 6) { aStatus.textContent = "Crop box too small"; return; }
    doEdit({ op: "crop", x: Math.round(bx * scale), y: Math.round(by * scale), cw: Math.round(bw * scale), ch: Math.round(bh * scale) }).then(endCrop);
  }

  function renderInfo(j) {
    aInfo.innerHTML =
      '<dt>File name</dt><dd>' + (j.name || "") + '</dd>'
      + '<dt>Type</dt><dd>' + (j.type || "—") + '</dd>'
      + '<dt>Size</dt><dd>' + fmtSize(j.size) + '</dd>'
      + '<dt>Dimensions</dt><dd>' + (j.w ? j.w + " × " + j.h + " px" : "—") + '</dd>';
  }

  function doEdit(params) {
    aStatus.textContent = "Working…";
    var fd = new FormData(); fd.append("name", aName); fd.append("_csrf", CFG.csrf || "");
    Object.keys(params).forEach(function (k) { fd.append(k, params[k]); });
    return fetch(CFG.mediaEdit, { method: "POST", body: fd, credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j && j.ok) {
          var bust = j.url + "?v=" + Date.now();
          aImg.src = bust;
          renderInfo({ name: aName, type: aType, size: j.size, w: j.w, h: j.h });
          if (aTile) aTile.querySelector(".media-img").style.backgroundImage = "url('" + bust + "')";
          aStatus.textContent = "Image updated";
        } else { aStatus.textContent = (j && j.error) || "Edit failed"; }
        return j;
      });
  }

  function saveDetails() {
    aStatus.textContent = "Saving…";
    var fd = new FormData();
    fd.append("name", aName); fd.append("title", aTitle.value); fd.append("alt", aAlt.value); fd.append("_csrf", CFG.csrf || "");
    fetch(CFG.mediaSave, { method: "POST", body: fd, credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (j) { aStatus.textContent = (j && j.ok) ? "Details saved" : ((j && j.error) || "Save failed"); });
  }

  function openAttach(name, url, tileEl) {
    if (!att) buildAttach();
    aName = name; aUrlStr = url; aTile = tileEl || null;
    endCrop(); att.classList.remove("scaling");
    aImg.src = url + "?v=" + Date.now();
    aTitle.value = ""; aAlt.value = ""; aUrl.value = url; aUsed.innerHTML = ""; aStatus.textContent = "Loading…";
    aInfo.innerHTML = "";
    att.classList.add("open");
    fetch(CFG.mediaInfo + "?name=" + encodeURIComponent(name), { credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || j.error) { aStatus.textContent = (j && j.error) || "Not found"; return; }
        aType = j.type || "";
        renderInfo(j);
        aTitle.value = j.title || ""; aAlt.value = j.alt || ""; aUrl.value = j.url; aUrlStr = j.url;
        if (j.used && j.used.length) {
          aUsedWrap.style.display = "";
          aUsed.innerHTML = j.used.map(function (u) { return '<a href="' + u.url + '" target="_blank">' + u.title + '</a>'; }).join("");
        } else { aUsed.innerHTML = '<span class="ma-unused">Not used on any product.</span>'; aUsedWrap.style.display = ""; }
        if (!j.gd) { aStatus.textContent = "Note: image editing unavailable on this server."; }
        else aStatus.textContent = "";
      });
  }
  function hideAttach() { endCrop(); if (att) att.classList.remove("open"); }

  // ---------- Media library page ----------
  var pageUpload = document.getElementById("media-upload");
  if (pageUpload) {
    var pageGrid = document.getElementById("media-grid");
    var pageStatus = document.getElementById("media-status");
    function tile(url, name) {
      var d = el("div", "media-tile");
      d.setAttribute("data-name", name); d.setAttribute("data-url", url);
      d.innerHTML = '<div class="media-img" style="background-image:url(\'' + url + '\')"></div>'
        + '<div class="media-meta"><span title="' + name + '">' + name + '</span>'
        + '<button type="button" class="media-del" title="Delete">&times;</button></div>';
      return d;
    }
    pageUpload.addEventListener("change", function () {
      var files = Array.prototype.slice.call(pageUpload.files || []);
      if (!files.length) return;
      pageStatus.textContent = "Uploading " + files.length + "…";
      var done = 0, ok = 0;
      files.forEach(function (f) {
        uploadFile(f).then(function (j) {
          done++;
          if (j && j.url) {
            ok++;
            var name = j.url.split("/").pop();
            if (!pageGrid) { location.reload(); return; }
            pageGrid.insertBefore(tile(j.url, name), pageGrid.firstChild);
          }
          if (done === files.length) pageStatus.textContent = ok + " uploaded";
        });
      });
      pageUpload.value = "";
    });
    document.addEventListener("click", function (e) {
      var del = e.target.closest && e.target.closest(".media-del");
      if (del) {
        e.stopPropagation();
        var t = del.closest(".media-tile");
        if (!t || !confirm("Delete this image? Products using it will lose the photo.")) return;
        deleteFile(t.getAttribute("data-name")).then(function (j) {
          if (j && j.ok) t.remove(); else pageStatus.textContent = (j && j.error) || "Delete failed";
        });
        return;
      }
      var tileEl = e.target.closest && e.target.closest(".media-tile");
      if (tileEl) openAttach(tileEl.getAttribute("data-name"), tileEl.getAttribute("data-url"), tileEl);
    });
  }
})();
