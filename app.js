(function () {
  const sidebar = document.getElementById("sidebar");
  const toggle = document.querySelector("[data-toggle-sidebar]");
  if (toggle && sidebar) {
    toggle.addEventListener("click", function () {
      sidebar.classList.toggle("open");
    });
  }

  document.querySelectorAll("a").forEach(function (link) {
    try {
      const href = new URL(link.href, window.location.href);
      if (href.pathname === window.location.pathname) {
        link.classList.add("active");
      }
    } catch (e) {
      /* ignore */
    }
  });

  document.querySelectorAll("form[data-confirm]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      if (!window.confirm(form.getAttribute("data-confirm"))) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll("[data-confirm][name='decision']").forEach(function (btn) {
    btn.addEventListener("click", function (event) {
      if (!window.confirm(btn.getAttribute("data-confirm"))) {
        event.preventDefault();
      }
    });
  });

  const start = document.getElementById("start_date");
  const end = document.getElementById("end_date");
  const form = document.getElementById("leave-form");
  if (form && start && end) {
    form.addEventListener("submit", function (event) {
      if (start.value && end.value && end.value < start.value) {
        event.preventDefault();
        window.alert("End date cannot be before start date.");
      }
    });
  }
})();
