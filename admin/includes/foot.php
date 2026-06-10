    </main>
</div>

<script src="/resources/js/admin.js"></script>
<script>
async function logout() {
    await fetch('/admin/api/auth/logout', { method: 'POST' });
    window.location.href = '/admin/login';
}
</script>
</body>
</html>
