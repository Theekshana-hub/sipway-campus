</main>

</div>

<script>
  const sidebar   = document.getElementById('sidebar');
  const burgerBtn = document.getElementById('burgerBtn');
  const backdrop  = document.getElementById('backdrop');

  function openSidebar(){ sidebar.classList.add('open'); backdrop.classList.add('show'); }
  function closeSidebar(){ sidebar.classList.remove('open'); backdrop.classList.remove('show'); }

  burgerBtn.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });
  backdrop.addEventListener('click', closeSidebar);

  const userMenu     = document.getElementById('userMenu');
  const userDropdown = document.getElementById('userDropdown');
  userMenu.addEventListener('click', (e) => {
    userDropdown.classList.toggle('show');
    e.stopPropagation();
  });
  document.addEventListener('click', () => userDropdown.classList.remove('show'));
</script>