// Tiptap WYSIWYG for <textarea class="richtext">.
// Self-hosted single bundle (no CDN waterfall) — see tiptap-bundle.js.
import { Editor, StarterKit, Link } from "./tiptap-bundle.js";

document.querySelectorAll("textarea.richtext").forEach(function (ta) {
  var wrap = document.createElement("div");
  wrap.className = "tiptap-editor";
  var toolbar = document.createElement("div");
  toolbar.className = "tiptap-toolbar";
  var mount = document.createElement("div");
  ta.style.display = "none";
  ta.parentNode.insertBefore(wrap, ta);
  wrap.appendChild(toolbar);
  wrap.appendChild(mount);

  var editor = new Editor({
    element: mount,
    extensions: [StarterKit, Link.configure({ openOnClick: false, HTMLAttributes: { rel: "noopener" } })],
    content: ta.value || "",
    onUpdate: function (p) { ta.value = p.editor.getHTML(); },
  });
  // submit safety
  var form = ta.closest("form");
  if (form) form.addEventListener("submit", function () { ta.value = editor.getHTML(); });

  function btn(label, fn) {
    var b = document.createElement("button");
    b.type = "button"; b.innerHTML = label;
    b.addEventListener("click", function () { fn(); });
    toolbar.appendChild(b);
  }
  btn("<strong>B</strong>", function () { editor.chain().focus().toggleBold().run(); });
  btn("<em>I</em>", function () { editor.chain().focus().toggleItalic().run(); });
  btn("H2", function () { editor.chain().focus().toggleHeading({ level: 2 }).run(); });
  btn("H3", function () { editor.chain().focus().toggleHeading({ level: 3 }).run(); });
  btn("¶", function () { editor.chain().focus().setParagraph().run(); });
  btn("• List", function () { editor.chain().focus().toggleBulletList().run(); });
  btn("1. List", function () { editor.chain().focus().toggleOrderedList().run(); });
  btn("❝", function () { editor.chain().focus().toggleBlockquote().run(); });
  btn("Link", function () {
    var url = window.prompt("Link URL", "https://");
    if (url === null) return;
    if (url === "") editor.chain().focus().unsetLink().run();
    else editor.chain().focus().extendMarkRange("link").setLink({ href: url }).run();
  });
  btn("Clear", function () { editor.chain().focus().unsetAllMarks().clearNodes().run(); });
});
