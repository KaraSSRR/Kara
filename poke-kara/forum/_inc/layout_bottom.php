        </div>
    </main>
    <footer class="forum-footer">
        <div class="small">© <?=date('Y')?> · Форум</div>
    </footer>
</div>
<script>
window.FORUM = window.FORUM || {};
FORUM.base = <?=json_encode(rtrim(FORUM_BASE,'/'))?>;
FORUM.csrf = <?=json_encode(csrf_token())?>;
FORUM.canReact = <?=json_encode((bool)$canReact)?>;
FORUM.canUpload = <?=json_encode((bool)$canUpload)?>;
</script>
<script src="<?=h(forum_url('/assets/forum.js'))?>"></script>
</body>
</html>
