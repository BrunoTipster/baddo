<?php
/**
 * Footer padrão do sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:37:10
 */
?>
    </div><!-- /.main-container -->

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Sobre <?php echo SITE_NAME; ?></h5>
                    <p class="mb-3">Conectando pessoas e criando relacionamentos significativos.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
                <div class="col-md-2">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Sobre</a></li>
                        <li><a href="#" class="text-white">Contato</a></li>
                        <li><a href="#" class="text-white">Blog</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Termos</a></li>
                        <li><a href="#" class="text-white">Privacidade</a></li>
                        <li><a href="#" class="text-white">Cookies</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Newsletter</h5>
                    <p class="mb-2">Receba nossas novidades</p>
                    <form class="mb-3">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Seu email">
                            <button class="btn btn-primary" type="submit">Assinar</button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 
                    Todos os direitos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>Desenvolvido por BrunoTipster</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <?php if (isset($additionalJs)): ?>
        <?php foreach ($additionalJs as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($additionalScripts)): ?>
        <script>
            <?php echo $additionalScripts; ?>
        </script>
    <?php endif; ?>
</body>
</html>