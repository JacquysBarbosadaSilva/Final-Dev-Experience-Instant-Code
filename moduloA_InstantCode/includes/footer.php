<?php if ($show_sidebar ?? false): ?>
        </div>
    </main>
<?php endif; ?>

<script src="js/sweetalert.js"></script>

<?php if (isset($page_specific_js)): ?>
    <script src="<?php echo $page_specific_js; ?>"></script>
<?php endif; ?>

</body>
</html>