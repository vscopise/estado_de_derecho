<div class="modal fade" id="login-modal" style="display-none">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div id="div-forms">
                                <form id="login-form">
                                    <div class="modal-body">
                                        <h3>
                                            <?php _e('Iniciar sesión', 'ascent') ?>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </h3>
                                        <hr />
                                        <div class="form-field">
                                            <label>Usuario</label>
                                            <input class="form-control input-lg required" type="text" id="login_user_name" value="<?php echo esc_attr(stripslashes($user_login)); ?>" />
                                        </div>
                                        <div class="form-field">
                                            <label>Contraseña</label>
                                            <input class="form-control input-lg required" type="password" id="login_user_pass" value="" />
                                        </div>
                                        <div class="login_fields">
                                            <?php wp_nonce_field('login_user','login_user_nonce', true, true ); ?>
                                            <input type="submit" value="Entrar" class="user-submit" id="btn-login" />
                                            <div id="mensajes-login"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div>
                                            <button id="login_lost_btn" type="button" class="btn btn-link"><?php _e('Recuperar Contraseña', 'ascent') ?></button>
                                            <button id="login_register_btn" type="button" class="btn btn-link"><?php _e('Registrarse', 'ascent') ?></button>
                                        </div>
                                    </div>
                                </form>
                                <form id="lost-form" style="display:none;">
                                    <div class="modal-body">
                                        <h3>
                                            Recuperar contraseña
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </h3>
                                        <hr />
                                        <p>Introduzca el nombre de usuario o el correo electrónico que utilizó en su perfil. Se le enviará un enlace de restablecimiento de contraseña por correo electrónico.</p>
                                        <div class="form-field">
                                            <label>Usuario o E-mail</label>
                                            <input class="form-control input-lg required" id="user_mail" />
                                        </div>
                                        
                                        <div class="login_fields">
                                            <?php wp_nonce_field('lost_password', 'lost_password_nonce', true, true ); ?>
                                            <input type="submit" value="Enviar !" />
                                            <div id="mensajes-lost"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div>
                                            <button id="lost_login_btn" type="button" class="btn btn-link">Iniciar Sesión</button>
                                            <button id="lost_register_btn" type="button" class="btn btn-link">Registrarse</button>
                                        </div>
                                    </div>
                                </form>
                                <form id="register-form" style="display:none;">
                                    <div class="modal-body">
                                        <h3>
                                            Registro
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </h3>
                                        <hr />
                                        <div class="form-field">
                                            <label>Usuario</label>
                                            <input class="form-control input-lg required" id="new_user_name" />
                                        </div>
                                        <div class="form-field">
                                            <label>E-mail</label>
                                            <input class="form-control input-lg required" id="new_user_mail"/>
                                        </div>
                                        <div class="login_fields">
                                            <?php wp_nonce_field( 'new_user', 'new_user_nonce', true, true ); ?>
                                            <input type="submit" value="Enviar !" id="btn-new-user" />
                                            <div id="mensajes-register"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div>
                                            <button id="register_login_btn" type="button" class="btn btn-link"><?php _e('Iniciar Sesión', 'ascent') ?></button>
                                            <button id="register_lost_btn" type="button" class="btn btn-link"><?php _e('Recuperar Contraseña', 'ascent') ?></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>