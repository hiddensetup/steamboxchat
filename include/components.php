<?php

function sb_profile_box()
{ ?>
    <div class="sb-profile-box sb-lightbox">
        <div class="sb-top-bar">
            <div class="sb-profile">
                <img src="<?php echo STMBX_URL ?>/media/user.svg" />
                <span class="sb-name"></span>
            </div>
            <div>
                <a data-value="email" class="sb-btn-icon" data-sb-tooltip="<?php sb_e('Send email') ?>">
                    <i class="bi-envelope-at"></i>
                </a>
                <a data-value="sms" class="sb-btn-icon" data-sb-tooltip="<?php sb_e('Send text message') ?>">
                    <i class="bi-chat-square-dots"></i>
                </a>
                <?php if (((sb_is_agent(false, true, true) && !sb_supervisor()) || sb_get_multi_setting('agents', 'agents-edit-user')) || (sb_supervisor() && sb_get_multi_setting('supervisor', 'supervisor-edit-user'))) echo ' <a class="sb-edit sb-btn sb-icon" data-button="toggle" data-hide="sb-profile-area" data-show="sb-edit-area"><i class="bi-pencil-square"></i>' . sb_('Edit user') . '</a>' ?>


                <a class="sb-select sb-btn sb-icon">
                    <i class="bi-plus-lg"></i><?php sb_e('New chat') ?>
                    <ul id="getSource" class="desktop-dropmod">


                        <?php include SB_PATH . "/apps/" . $sb_apps[0] . "/functions.php";
                        $cloud_active = sb_get_multi_setting('whatsapp-cloud', 'cloud-active'); ?>
                        <?php if ($cloud_active) : ?>
                            <li class="sb-start-conversation" onclick="updateSource('wa')">
                                <?php sb_e('<i class="bi-whatsapp"></i> WhatsApp API') ?>
                            </li>
                            <hr>

                        <?php endif; ?>
                        <li class="sb-start-tk-conversation" onclick="updateSource('tk')">
                            <?php sb_e('<i class="bi-chat-text"></i> Live chat') ?>
                        </li>
                        <?php include SB_PATH . "/apps/" . $sb_apps[1] . "/functions.php";
                        $goproxy = !empty(sb_get_multi_setting('whatsmeow-go', 'whatsmeow-go-active')); ?>
                        <?php if ($goproxy) : ?>
                            <li class="sb-start-qr-conversation" onclick="updateSource('ww')">
                                <?php sb_e('<i class="sb-start-conversation bi-whatsapp"></i> WhatsApp <small style="color:var(--color-green);">(2)</small>'); ?>
                            </li>
                        <?php endif; ?>
                        <?php include SB_PATH . "/apps/" . $sb_apps[2] . "/functions.php";
                        $goproxy = !empty(sb_get_multi_setting('waweb-go', 'waweb-go-active')); ?>
                        <?php if ($goproxy) : ?>
                            <li class="sb-start-qr-conversation" onclick="updateSource('wx')">
                                <?php sb_e('<i class="sb-start-conversation bi-whatsapp"></i> WhatsApp <small style="color:var(--color-green);">(3)</small>'); ?>
                            </li>
                        <?php endif; ?>

                    </ul>

                </a>
                <a class="sb-close sb-btn-icon" data-button="toggle" data-hide="sb-profile-area" data-show="sb-table-area">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="sb-main sb-scroll-area">

            <div>
                <div class="sb-title">
                    <?php sb_e('Profile') ?>
                </div>
                <div class="sb-agent-area"></div>
                <div class="sb-profile-list sb-profile-list-conversation<?php echo $collapse ?>"></div>

            </div>
            <div class="profile-log-background">
                <div class="sb-title">
                    <!-- <div><?php sb_e('Conversation history') ?></div> -->
                    <p class="profile-bubble-message">Desde aquí puedes <strong>continuar conversaciones existentes 🧑&zwj;💻</strong>. Pulsa sobre la conversación que deseas continuar (recomendado) o crea una conversación nueva pulsando <strong> + Nuevo </strong>.</p>

                    <p class="profile-bubble-message"> *Ten en cuenta que en Routin.bot tienes la opción de generar conversaciones separadas por agente (como tickets) o continuar conversaciones existentes. El cliente siempre verá la misma conversación de WhatsApp.</p>

                </div>
                <ul class="sb-user-conversations"></ul>
            </div>
        </div>
    </div>
<?php } ?>

<?php

/*
 * ----------------------------------------------------------
 * PROFILE EDIT BOX
 * ----------------------------------------------------------
 *
 * Profile editing area used in admin side
 *
 */

function sb_profile_edit_box()
{ ?>
    <div class="sb-profile-edit-box sb-lightbox">
        <div class="sb-info"></div>
        <div class="sb-top-bar">
            <div class="sb-profile">
                <img src="<?php echo STMBX_URL ?>/media/user.svg" />
                <span class="sb-name"></span>
            </div>
            <div>
                <a class="sb-save sb-btn sb-icon">
                    <i class="bi-check-lg"></i><?php sb_e('Save changes') ?>
                </a>
                <a class="sb-close sb-btn-icon" data-button="toggle" data-hide="sb-profile-area" data-show="sb-table-area">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="sb-main sb-scroll-area">

            <div class="sb-details">
                <div class="sb-title">
                    <?php sb_e('Edit details') ?>
                </div>


                <div class="sb-edit-box">
                    <div id="profile_image" data-type="image" class="sb-input sb-input-image sb-profile-image">
                        <span><?php sb_e('Profile image') ?></span>
                        <div class="image">
                            <div class="bi-x-lg"></div>
                        </div>
                    </div>

                    <div id="user_type" data-type="select" name="user_type" class="sb-input sb-input-select">
                        <span><?php sb_e('Type') ?></span>
                        <select name="user_type">
                            <option value="agent"><?php sb_e('Agent') ?></option>
                            <option value="admin"><?php sb_e('Admin') ?></option>
                            <option value="lead"><?php sb_e('Lead') ?></option>
                        </select>
                    </div>

                    <?php sb_departments('select') ?>

                    <div id="first_name" data-type="text" name="first_name" class="sb-input">
                        <span><?php sb_e('First name') ?></span>
                        <input type="text" name="first_name" required />
                    </div>

                    <div id="last_name" data-type="text" name="last_name" class="sb-input">
                        <span><?php sb_e('Last name') ?></span>
                        <input type="text" name="last_name" />
                    </div>

                    <div id="password" data-type="password" name="password" class="sb-input">
                        <span><?php sb_e('Password') ?></span>
                        <input type="text" name="password" />
                    </div>

                    <div id="email" data-type="email" name="email" class="sb-input">
                        <span><?php sb_e('Email') ?></span>
                        <input type="email" name="email" />
                    </div>
                </div>




                <a class="sb-delete sb-btn-text sb-btn-red">
                    <i class="bi-trash"></i><?php sb_e('Delete user') ?>
                </a>
            </div>
            <div class="sb-additional-details">
                <div class="sb-title">
                    <?php sb_e('Edit additional details') ?>
                </div>
                <!--// added  ↓ ↑ -->
                <div class="sb-edit-box">
                    <div id="phone" data-type="text" name="phone" class="sb-input">
                        <span><?php sb_e('Phone') ?></span>
                        <input type="text" name="phone" />
                    </div>

                    <div id="address" data-type="text" name="address" class="sb-input">
                        <span><?php sb_e('Address') ?></span>
                        <input type="text" name="address" />
                    </div>

                    <div id="city" data-type="text" name="city" class="sb-input">
                        <span><?php sb_e('City') ?></span>
                        <input type="text" name="city" />
                    </div>

                    <div id="country" data-type="select" class="sb-input">
                        <span><?php sb_e('Country') ?></span>
                        <?php echo sb_select_countries() ?>
                    </div>

                    <div id="postal_code" data-type="text" name="postal_code" class="sb-input">
                        <span><?php sb_e('Postal code') ?></span>
                        <input type="text" name="postal_code" />
                    </div>

                    <div id="language" data-type="select" class="sb-input">
                        <span><?php sb_e('Language') ?></span>
                        <?php echo sb_select_languages() ?>
                    </div>

                    <div id="birthdate" data-type="date" name="birthdate" class="sb-input">
                        <span><?php sb_e('Birthdate') ?></span>
                        <input type="date" name="birthdate" />
                    </div>

                    <div id="company" data-type="text" name="company" class="sb-input">
                        <span><?php sb_e('Company') ?></span>
                        <input type="text" name="company" />
                    </div>

                    <div id="facebook" data-type="text" name="facebook" class="sb-input">
                        <span><?php sb_e('Facebook') ?></span>
                        <input type="text" name="facebook" />
                    </div>

                    <div id="twitter" data-type="text" name="twitter" class="sb-input">
                        <span><?php sb_e('X') ?></span>
                        <input type="text" name="twitter" />
                    </div>

                    <div id="linkedin" data-type="text" name="linkedin" class="sb-input">
                        <span><?php sb_e('LinkedIn') ?></span>
                        <input type="text" name="linkedin" />
                    </div>

                    <div id="website" data-type="text" name="website" class="sb-input">
                        <span><?php sb_e('Website') ?></span>
                        <input type="text" name="website" />
                    </div>

                    <div id="timezone" data-type="text" name="timezone" class="sb-input">
                        <span><?php sb_e('Timezone') ?></span>
                        <input type="text" name="timezone" />
                    </div>

                    <?php
                    $additional_fields = sb_get_setting('user-additional-fields');
                    if ($additional_fields != false && is_array($additional_fields)) {
                        $code = '';
                        for ($i = 0; $i < count($additional_fields); $i++) {
                            $value = $additional_fields[$i];
                            if ($value['extra-field-name'] != '') {
                                $code .= '<div id="' . $value['extra-field-slug'] . '" data-type="text" class="sb-input"><span>' . $value['extra-field-name'] . '</span><input type="text" name="' . $value['extra-field-slug'] . '"></div>';
                            }
                        }
                        echo $code;
                    }
                    ?>
                </div>

            </div>
        </div>
    </div>
<?php } ?>
<?php

/*
 * ----------------------------------------------------------
 * LOGIN BOX
 * ----------------------------------------------------------
 *
 * Administration area login box
 *
 */

function displayMessage()
{
    $jsonString = '{
        "payment": "<h2 style=\"color:var(--chat-text-primary)\"><i class=\"bi-info-circle-fill\"></i> ¡Pago Requerido! </h2><span style=\"color:var(--chat-text-primary)\">Para seguir disfrutando de Routin Cloud, necesitas realizar un pago. ¡No dejes que la diversión se detenga! Haz clic <button onclick=\"window.location.href=\'' . PAYMENT_LINK . '\'\">aquí</button> para realizar tu pago ahora mismo.</span><br><br><span style=\"color:var(--chat-text-primary)\">¿Necesitas ayuda? No dudes en contactarnos.</span>",
        "trial": "<h2 style=\"color:var(--chat-text-primary)\"> ¡Prueba Finalizada! </h2><span style=\"color:var(--chat-text-primary)\">Tu período de prueba ha terminado. ¡Pero no te preocupes! Puedes seguir disfrutando de Routin.bot mientras eliges un plan. Continua ahora por solo $8.5 USD por día. Haz clic en el botón a continuación para continuar tu experiencia.</span><br><br><button class=\"sb-btn\" style=\"background: var(--chat-app-theme-color);width: 75%; margin: 10px auto;border:none;box-shadow:var(--box-shadow-bubble-chat);\" onclick=\"window.location.href=\'' . PAYMENT_LINK . '\'\">Continuar 1 día más</button><br><br><span style=\"color:var(--chat-text-primary)\"><span style=\"color:var(--chat-text-primary)\">¿Sabías que con Routin.bot también puedes generar códigos QR dinámicos y acortar enlaces con tu propio dominio? Accede gratis desde <a style=\"color:var(--chat-text-url);font-size:13px\" href=\"https://qrcode.steamboxchat.com\">qrcode.steamboxchat.com</a></span>",
        "overloaded": "<h2 style=\"color:var(--chat-text-primary)\"><i class=\"bi-info-circle-fill\"></i> ¡Sistema Sobrecargado! </h2><span style=\"color:var(--chat-text-primary)\">Estamos experimentando una alta demanda en nuestros servidores en este momento. Por favor, sé paciente y vuelve a intentarlo más tarde. Agradecemos tu comprensión.</span><br><br><span style=\"color:var(--chat-text-primary)\">Mientras tanto, ¿por qué no exploras otras funciones de Steambox? ¡Hay mucho por descubrir!</span>"
    }
    ';



    $messages = json_decode($jsonString, true);

    $message_type = defined('MESSAGE_TYPE') ? MESSAGE_TYPE : '';

    if (array_key_exists($message_type, $messages)) {
        $message = $messages[$message_type];

        echo '<div id="login-message" style="padding-top:40px">';
        echo '<div class="alert-special">';
        echo $message;
        echo '</div>';
        echo '</div>';
        return true;
    } else {
        return false;
    }
}

function sb_login_box()
{
    $messageDisplayed = displayMessage();
?>
    <form class="sb sb-rich-login sb-admin-box sb-form-container" <?php echo ($messageDisplayed ? 'style="display:none;"' : ''); ?>>
        <div></div>
        <div class="sb-top-bar">
            <div id="announcement">
                <?php
                if (!$messageDisplayed) {
                ?>
                    <img style="margin: 50px auto 10px auto;" src="<?php echo sb_get_setting('login-icon') != false ? sb_get_setting('login-icon') : '/media/routin.svg' ?>" />
                <?php } ?>
            </div>
        </div>
        <div class="sb-main" id="email">
            <div class="sb-input">
                <label style="color: var(--chat-text-secondary); font-size:14px; position: relative; top: 19px; left: 0px; background: var(--chat-app-background); padding: 1px 5px;" for="text"><?php sb_e('Email') ?></label>
                <input style="width: calc(100% - 30px);height: 45px;" type="text" />
            </div>
            <div class="sb-block-space"></div>
            <div class="sb-input" id="password">
                <label style=" color: var(--chat-text-secondary); font-size:14px; position: relative; top: 19px; left: 0px; background: var(--chat-app-background); padding: 1px 5px; " for="password"><?php sb_e('Password') ?></label>
                <input style="width: calc(100% - 30px);height: 45px;" type="password" />
            </div>
            <div class="sb-bottom">
                <div style="padding: 5px 90px;margin-top: 0px!important;width: fit-content!important;"" class=" sb-btn sb-submit-login"><?php sb_e('Login') ?></div>

            </div>


        </div>

        <div style="display: flex;flex-wrap: wrap;justify-content: space-evenly" class="sb-text">
            <div style="margin: 0.2rem auto 1rem auto;max-width: 270px;" class="sb-info"></div>
            <small>&copy; <?php echo date("Y"); ?> Routin Cloud</small>

        </div>
    </form>


    <img id="sb-error-check" style="display:none" src="<?php echo STMBX_URL . '/media/icon.svg' ?>" />
    <script>
        ! function(n) {
            n(document).ready(function() {
                n(".sb-admin-start").removeAttr("style"), n(".sb-submit-login").on("click", function() {
                    SBF.loginForm(this, !1, function() {
                        location.reload()
                    })
                }), n("#sb-error-check").one("error", function() {}), SBPusher.initServiceWorker()
            }), n(window).keydown(function(i) {
                13 == i.which && n(".sb-submit-login").click()
            })
        }(jQuery);
    </script>


<?php } ?>
<?php

/*
 * ----------------------------------------------------------
 * CONFIRMATION ALERT BOX
 * ----------------------------------------------------------
 *
 * Ask a yes / no question to confirm an operation
 *
 */

function sb_dialog()
{ ?>
    <div class="sb-dialog-box sb-lightbox">
        <div class="sb-title"></div>
        <p></p>
        <hr style="margin: 20px;background: var(--chat-border-color);">
        <div style="display: flex;flex-wrap: wrap;justify-content: flex-end;">
            <a class="sb-confirm sb-btn"><?php sb_e('Confirm') ?></a>
            <a class="sb-cancel sb-btn sb-btn-red"><?php sb_e('Cancel') ?></a>
            <a class="sb-close sb-btn"><?php sb_e('Close') ?></a>
        </div>
    </div>
<?php } ?>

<?php

/*
 * ----------------------------------------------------------
 * UPDATES BOX
 * ----------------------------------------------------------
 *
 * Display the updates box
 *
 */

function sb_updates_box()
{ ?>
    <div class="sb-lightbox sb-updates-box">
        <style>
            /* Your CSS styles */
            .div-1 {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: space-between;
                align-items: center;
                max-width: 600px;
                margin: auto;
                padding: 0;
                flex-wrap: wrap;
            }

            .h2-style {
                background: var(--chat-lightbox-top);
                border-radius: var(--chat-rounded-top-1-0);
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0;
                color: var(--chat-btn-background);
            }

            #updtsRoadmap {
                height: 520px;
                margin-bottom: 3rem;
                margin: 10px 20px;
            }

            .container {
                margin: 10px;
            }

            .status-item {
                display: flex;
                justify-content: space-between;
                margin: 10px 0px;
                align-items: center;
                margin: 10px 20px;

            }

            .info-section {
                display: flex;
                align-items: flex-start;
                justify-content: center;
                margin-bottom: 20px;
            }

            .info-text {
                max-width: 600px;
            }

            h2 {
                font-size: 1.5rem;
                color: #333;
                margin-bottom: 10px;
            }

            .notification-card {
                background-color: var(--chat-app-background);
                border: 1px solid var(--chat-border-color);
                border-radius: 5px;
                padding: 10px;
                margin: 5px 0;
                font-size: 0.9rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            }

            .notification-card:hover {
                background-color: var(--chat-background-menu-selected) !important;
                border: 1px solid var(--chat-border-color);
                border-radius: 5px;
                padding: 10px;
                margin: 5px 0;
                font-size: 0.9rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            }

            .help-videos {
                display: flex;
                flex-direction: column;
            }




            .notification-card.latest {
                border: 1px solid var(--chat-border-color);
                background: var(--chat-background-menu-selected);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }

            .notification-card p {
                margin: 0;
            }

            .tiny-button {
                font-size: 0.8rem;
                color: var(--chat-btn-text);
                background-color: var(--chat-btn-background);
                border: none;
                border-radius: 3px;
                cursor: pointer;
                padding: 5px 10px;
                margin-top: 5px;
            }

            .tiny-button:hover {
                background-color: var(--chat-btn-background-active);
                color: var(--chat-btn-text-active);
            }
        </style>

        <div class="sb-top-bar">
            <div>Panel de <?php sb_e('Updates') ?></div>
            <div>
                <a class="sb-close sb-btn-icon">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="sb-scroll-area sb-main">
            <div>
                <div class="container">
                    <div class="info-section">
                        <div class="info-text">
                            <p>Estimados usuarios, Les informamos que a partir de ahora somos Routin.cloud Este cambio nos permitirá optimizar nuestros procesos y mejorar la eficiencia en la atención a sus necesidades. Agradecemos su comprensión y apoyo durante esta transición de Steamboxchat. Seguimos comprometidos en brindarles el mejor servicio posible.</p>
                        </div>
                    </div>
                    <div id="updtsRoadmap"></div>
                    <div>
                        <h2 class="h2-style">APIs</h2>
                        <div class="status-container">
                            <div class="status-item">
                                <h3> Telegram API </h3>
                                <div class="status-bar">
                                    <div class="status-bar-inner" id="telegram-bar">OK</div>
                                </div>
                            </div>
                            <div class="status-item">
                                <h3> Routin Cloud API </h3>
                                <div class="status-bar">
                                    <div class="status-bar-inner" id="routin-bar">OK</div>
                                </div>
                            </div>
                            <div class="status-item">
                                <h3> WhatsApp Cloud API </h3>
                                <div class="status-bar">
                                    <div class="status-bar-inner" id="whatsapp-web-bar">OK</div>
                                </div>
                            </div>
                            <div class="status-item">
                                <h3> WhatsApp API </h3>
                                <div class="status-bar">
                                    <div class="status-bar-inner" id="whatsapp-api-bar">OK</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h2 class="h2-style">Centro de ayuda</h2>
                        <div class="status-container">
                            <div class="help-videos">
                                <h3> Solicita el proceso que quieras aprender: verify@routin.cloud</h3>
                                <div class="status-bar">
                                    <div class="video-uploading" id="video-custom">

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <script>
                    function fetchUpdts() {
                        fetch(apiUrl)
                            .then(res => {
                                if (!res.ok) throw new Error('Network response was not ok: ' + res.statusText);
                                return res.text(); // Read the response as plain text
                            })
                            .then(text => {
                                try {
                                    const data = JSON.parse(text); // Attempt to parse the text as JSON
                                    const updtsRoadmap = document.getElementById("updtsRoadmap");
                                    updtsRoadmap.innerHTML = "";
                                    let hasNewUpdts = false,
                                        cutoffDate = new Date(Date.now() - 18e6);
                                    data.forEach((updts, index) => {
                                        let updtsDate = new Date(updts.commit.author.date),
                                            version = updts.sha.substring(0, 7),
                                            message = updts.commit.message;
                                        if (updtsDate > cutoffDate) hasNewUpdts = true;
                                        let card = document.createElement("div");
                                        card.className = "notification-card" + (index === 0 ? " latest" : "");
                                        card.innerHTML = `
                        <div><p><strong>▶︎ ${message}</strong></p>
                        <p>Actualizado: ${updtsDate.toLocaleDateString()}</p>
                        <p>Versión: ${version}</p></div>
                        ${index === 0 ? '<div><button class="tiny-button" onclick="clearCacheAndReload()"> Descargar <i class="bi bi-arrow-down-circle"></i></button></div>' : ""}
                    `;
                                        updtsRoadmap.appendChild(card);
                                    });
                                } catch (err) {
                                    console.error("Error parsing JSON:", err);
                                    document.getElementById("updtsRoadmap").innerHTML = `<p>Error: ${err.message}</p>`;
                                }
                            })
                            .catch(err => {
                                console.error("Fetch Error:", err);
                                document.getElementById("updtsRoadmap").innerHTML = `<p>Error: ${err.message}</p>`;
                            });
                    }


                    function clearCacheAndReload() {
                        if ("caches" in window) caches.keys().then(keys => keys.forEach(key => caches.delete(key)));
                        localStorage.clear();
                        sessionStorage.clear();
                        document.cookie.split(";").forEach(cookie => {
                            document.cookie = cookie.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                        });
                        location.reload(true);
                    }

                    document.addEventListener("DOMContentLoaded", function() {
                        fetchUpdts();
                        setInterval(fetchUpdts, 216e5);
                    });
                </script>
            </div>
        </div>
    </div>

<?php } ?>

<?php

/*
 * ----------------------------------------------------------
 * SYSTEM REQUIREMENTS BOX
 * ----------------------------------------------------------
 *
 * Display the system requirements box
 *
 */

function sb_requirements_box()
{ ?>
    <div class="sb-lightbox sb-requirements-box">
        <div class="sb-info"></div>
        <div class="sb-top-bar">
            <div><?php sb_e('System requirements') ?></div>
            <div>
                <a class="sb-close sb-btn-icon">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="sb-main"></div>
    </div>
<?php } ?>
<?php

/*
 * ----------------------------------------------------------
 * APP BOX
 * ----------------------------------------------------------
 *
 * Display the app box
 *
 */

function sb_app_box()
{ ?>
    <div class="sb-lightbox sb-app-box" data-app="">
        <div class="sb-info"></div>
        <div class="sb-top-bar">
            <div></div>
            <div>
                <a class="sb-close sb-btn-icon">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="sb-main">
            <p></p>
            <div class="sb-title"></div>
            <div class="sb-input-setting sb-type-text">
            </div>
            <div class="sb-bottom">
            </div>
        </div>
    </div>
<?php } ?>
<?php

/*
 * ----------------------------------------------------------
 * NOTES BOX
 * ----------------------------------------------------------
 *
 * Display the notes box
 *
 */

function sb_notes_box()
{ ?>
    <div class="sb-lightbox sb-notes-box">
        <div class="sb-info"></div>
        <div class="sb-top-bar">
            <div><?php sb_e('Add new note') ?></div>
            <div>
                <a class="sb-close sb-btn-icon">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="sb-main">
            <div class="sb-input-setting sb-type-textarea">
                <textarea maxlength="620" placeholder="<?php sb_e('Write here your note...') ?>"></textarea>
                <div class="load-reminder reminder-box sb-hide">
                    <div class="reminder-box-content">
                        <div class="sb-input-setting">
                            <input style="margin:10px 0px;" type="text" id="alertdate" name="datetimes" />
                            <select id="zones">
                                <option value="">select timezone</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sb-bottom" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap: wrap;flex-direction: column-reverse;">
                <div style="display:flex;">
                    <a style="" class="sb-add-note sb-btn sb-icon"><i class="bi-pencil"></i><?php sb_e('Save note') ?></a>
                </div>
            </div>
        </div>
    </div>
<?php } ?>


<?php

/*
 * ----------------------------------------------------------
 * TAGS BOX
 * ----------------------------------------------------------
 *
 * Display the tags box
 *
 */

function sb_tags_box()
{ ?>
    <div class="sb-lightbox sb-tags-box">
        <div class="sb-info"></div>
        <div class="sb-top-bar">
            <div><?php sb_e('Manage tags') ?></div>
            <div>
                <a class="sb-close sb-btn-icon">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="sb-main">
            <div class="sb-tags-cnt"></div>
            <div class="sb-bottom">
                <a id="sb-save-tags" class="sb-btn"><i class="bi-check-lg"></i>
                    <?php sb_e('Save changes') ?>
                </a>
            </div>
        </div>
    </div>
<?php } ?>


<?php

/*
 * ----------------------------------------------------------
 * DIRECT MESSAGE BOX
 * ----------------------------------------------------------
 *
 * Display the direct message box
 *
 */
function sb_direct_message_box()
{ ?>

    <div class="sb-lightbox sb-direct-message-box">
        <div class="sb-info"></div>
        <div class="sb-top-bar">
            <div>
            </div>
            <div>
                <a class="sb-close sb-btn-icon">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>


        <div class="sb-main sb-scroll-area">
            <p style="margin: 5px 0px;"> <?php sb_e('Enter user IDs separated by commas.') ?> </p>
            <div class="sb-input-setting sb-type-text sb-first" style="gap:10px;display: flex;width: 100%;justify-content: space-between;align-items: center;">
                <div class="sb-selector">
                    <?php
                    include '/apps/whatsmeow/functions.php';
                    $goproxy = !empty(sb_get_multi_setting('whatsmeow-go', 'whatsmeow-go-active'));
                    ?>
                    <?php
                    include '/apps/waweb/functions.php';
                    $goproxy = !empty(sb_get_multi_setting('waweb-go', 'waweb-go-active'));
                    ?>
                    <select class="sb-select" name="broadcast_type">
                        <option value="message"> <?php sb_e('Broadcast on existing chat') ?> </option>
                        <option value="template" class="active-bulk-sender"> <?php sb_e('Broadcast without existing chat') ?> </option>
                        <!-- <?php if ($goproxy) : ?><option value="sms"> <?php sb_e('Broadcast Whatsmeow') ?> </option> <?php endif; ?> -->
                        <!-- <?php if ($goproxy) : ?><option value="sms"> <?php sb_e('Broadcast WhatsApp Web') ?> </option> <?php endif; ?> -->
                    </select>
                </div>
                <input class="sb-direct-message-users" type="text" name="user_ids" placeholder="<?php sb_e('User IDs separated by commas') ?>">

            </div>
            <div class="sb-title sb-direct-message-subject"> <?php sb_e('Subject') ?> </div>
            <div class="sb-input-setting sb-type-text sb-direct-message-subject">
                <input type="text" name="email_subject" placeholder="<?php sb_e('Email subject') ?>">
            </div>
            <div class="sb-title sb-direct-message-title-subject sb-direct-message-hide"> <?php sb_e('Message') ?> </div>
            <div class="sb-input-setting sb-type-textarea sb-direct-message-hide">
                <textarea style="height:160px" name="message" placeholder="<?php sb_e('Write your message here...') ?>" required></textarea>
            </div>
            <div id="form-container" class="sb-bulk-sender sb-additional-details sb-hide">
                <form id="user-template-form" style="display: flex;flex-wrap: wrap;justify-content: space-around;gap: 10px;">
                    <div class="sb-input-setting api-cloud-bubble" style="width: 100%;max-width: 316px;background: var(--chat-text-theme-background);box-shadow: var(--box-shadow-inner);">
                        <div class="api-bubble-container">
                            <textarea disabled="" type="text" class="BodyTemplate textarea-api" style="height: auto!important;box-sizing: border-box;min-height: 10px;" name="BodyTemplate"></textarea>
                            <div class="FooterTemplate"></div>
                            <div class="Buttons"></div>

                        </div>
                    </div>
                    <div style="max-width: 100%; width: 100%; max-width: 320px; ">
                        <div class="sb-input-setting" style="gap:10px;display:flex;flex-direction: column;max-width: 100%;">

                            <select class="Language" name="Language" required>
                                <option value="es"> <?php sb_e('Español (es)') ?> </option>
                                <option value="es_AR"> <?php sb_e('Español (es_AR)') ?> </option>
                                <option value="en_US"> <?php sb_e('English (en)') ?> </option>
                                <option value="es_ES"> <?php sb_e('Español (es_ES)') ?> </option>
                                <option value="es_MX"> <?php sb_e('Español (es_MX)') ?> </option>
                            </select>
                            <select class="LoadedTemplate" name="LoadedTemplate">
                                <option value="">Select a template</option>
                            </select>
                            <!-- Add this new input for Image URL -->
                            <div style="display:flex"">
                            <input type=" text" class="ImageUrl" name="ImageUrl" style="width: -webkit-fill-available;margin-left: -1px;border-radius: var(--chat-rounded-size-6);" placeholder="<?php sb_e('Image URL Chat Area (optional)') ?>">
                            </div>
                            <div style="display: flex;flex-wrap: wrap;flex-direction: column;align-items: stretch;" class="sb-input-setting Variables">
                                <div class='variables'>
                                    <input type="text" name="variable" placeholder="{{1}}" style="margin: 2px 2px;">
                                </div>
                                <div style="display: flex;justify-content: flex-start;margin: 1px;gap: 5px;align-items: center;">
                                    <a type="button" class="RemVariableButton whatsapp_var_buttons_1 sb-btn"><i class="bi bi-dash-lg"></i></a>
                                    <a type="button" class="AddVariableButton whatsapp_var_buttons_2 sb-btn"><i class="bi bi-plus-lg"></i></a>
                                </div>
                            </div>
                            <div class="sb-bottom send-meta">
                                <button class="sb-btn sb-icon sb-send-direct-message" style="width:fit-content;border: none;text-align: end;">
                                    <i class="bi-cash-coin"></i> <?php sb_e('Send') ?>
                                </button>
                                <div></div>
                            </div>
                        </div>
                    </div>




                </form>

            </div>
            <div class="sb-bottom sb-direct-message-hide">
                <a class="sb-send-direct-message sb-direct-message-hide sb-btn sb-icon">
                    <i class="bi-megaphone"></i> <?php sb_e('Send') ?> </a>
                <div></div>
            </div>

        </div>

    </div>

<?php } ?>



<?php

/*
 * ----------------------------------------------------------
 * WHATSAPP META BOX
 * ----------------------------------------------------------
 *
 * Display the WhatsApp Meta lightbox in conversation section
 *
 */

function sb_send_template_box()
{ ?>
    <div class="sb-lightbox sb-send-template-box" style="height: calc(100% - 145px);" data-source="">
        <div class="sb-info"></div>
        <div class="sb-top-bar">
            <div>
                <i data-value="wa" class="bi-whatsapp"></i> <span style="margin-left:5px">Cloud API <small style="color:var(--color-green);">(1)</small> </span>
            </div>
            <div>
                <a class="sb-close sb-btn-icon">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>

        <div class="sb-main sb-scroll-area">
            <div id="form-container meta" style="display: flex;flex-direction: column;" class="sb-additional-details">
                <form id="template-form" style="display: flex;flex-wrap: wrap;justify-content: space-around;gap: 10px;">
                    <div class="sb-input-setting api-cloud-bubble" style="width: 100%;max-width: 316px;background: var(--chat-text-theme-background);box-shadow: var(--box-shadow-inner);">
                        <div class="api-bubble-container">
                            <textarea disabled="" type="text" class="BodyTemplate textarea-api" style="height: auto!important;box-sizing: border-box;min-height: 10px;" name="BodyTemplate"></textarea>
                            <div class="FooterTemplate"></div>
                            <div class="Buttons"></div>
                        </div>
                    </div>
                    <div style="max-width: 100%; width: 100%; max-width: 320px; ">
                        <div class="sb-input-setting" style="gap:10px;display:flex;flex-direction: column;max-width: 100%;">


                            <select class="Language" name="Language" required>
                                <option value="es"> <?php sb_e('Español (es)') ?> </option>
                                <option value="es_AR"> <?php sb_e('Español (es_AR)') ?> </option>
                                <option value="en_US"> <?php sb_e('English (en)') ?> </option>
                                <option value="es_ES"> <?php sb_e('Español (es_ES)') ?> </option>
                                <option value="es_MX"> <?php sb_e('Español (es_MX)') ?> </option>
                            </select>
                            <select class="LoadedTemplate" name="LoadedTemplate" id="templateSelect">
                                <option value="">Select a template</option>
                            </select>
                            <!-- Add this new input for Image URL -->
                            <div style="display:flex"">
                                    <input type=" text" class="ImageUrl" name="ImageUrl" style="width: -webkit-fill-available;margin-left: -1px;border-radius: var(--chat-rounded-size-6);" placeholder="<?php sb_e('Image URL Chat Area (optional)') ?>">
                            </div>
                            <div style="display: flex;flex-wrap: wrap;flex-direction: column;align-items: stretch;" class="sb-input-setting Variables">
                                <div class='variables'>
                                    <input type="text" name="variable" placeholder="{{1}}" style="margin: 2px 2px;">
                                </div>
                                <div style="display: flex;justify-content: flex-start;margin: 1px;gap: 5px;align-items: center;">
                                    <a type="button" class="RemVariableButton whatsapp_var_buttons_1 sb-btn"><i class="bi bi-dash-lg"></i></a>
                                    <a type="button" class="AddVariableButton whatsapp_var_buttons_2 sb-btn"><i class="bi bi-plus-lg"></i></a>
                                </div>
                            </div>
                            <div class="sb-bottom send-meta">
                                <button class="sb-repeater-add  sb-btn sb-icon" style="border: none;text-align: end;" type="submit">
                                    <i class="bi-cash-coin"></i> <?php sb_e('Send') ?>
                                </button>
                                <div>

                                </div>
                            </div>
                        </div>
                    </div>
            </div>

            </form>
        </div>


    </div>
    <script>
        const meta = new Metatemplate;
        meta.init("#template-form");

        const themeToggleBtns = document.querySelectorAll('.themeToggleBtn');
        const htmlTag = document.documentElement;
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');

        const updateTheme = () => {
            const currentTheme = htmlTag.dataset.theme;
            let newTheme;
            let newThemeColor;

            switch (currentTheme) {
                case 'steambox':
                    newTheme = 'light';
                    newThemeColor = 'white';
                    htmlTag.classList.remove('steambox');
                    break;
                case 'light':
                    newTheme = 'app';
                    newThemeColor = 'white';
                    htmlTag.classList.remove('steambox');
                    break;
                case 'app':
                    newTheme = 'routin';
                    newThemeColor = 'white';
                    htmlTag.classList.remove('steambox');
                    break;
                case 'routin':
                    newTheme = 'apollo';
                    newThemeColor = 'white';
                    htmlTag.classList.remove('steambox');
                    break;
                case 'apollo':
                default:
                    newTheme = 'steambox';
                    newThemeColor = '#181620';
                    htmlTag.classList.add('steambox');
                    break;
            }

            htmlTag.dataset.theme = newTheme;
            metaThemeColor.content = newThemeColor;
            localStorage.setItem('theme', newTheme);
        };

        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            htmlTag.dataset.theme = storedTheme;
            switch (storedTheme) {
                case 'light':
                    metaThemeColor.content = 'white';
                    break;
                case 'app':
                    metaThemeColor.content = 'white';
                    break;
                case 'routin':
                    metaThemeColor.content = 'white';
                    break;
                case 'apollo':
                    metaThemeColor.content = 'white';
                    break;
                case 'steambox':
                default:
                    metaThemeColor.content = '#181620';
                    htmlTag.classList.add('steambox');
                    break;
            }
        } else {
            htmlTag.dataset.theme = 'light';
            metaThemeColor.content = 'white';
        }

        themeToggleBtns.forEach(btn => {
            btn.addEventListener('click', updateTheme);
        });

        // Rating
        const sendRatingButton = document.getElementById("send-rating-button");
        sendRatingButton.addEventListener("click", function() {
            SBChat.sendMessage(-1, "[rating]");
        });


        const sbIconDrag = document.querySelector(".menu-plus");
        const sbBarIcons = document.querySelector(".sb-bar-icons");
        const elementsToHideIcons = [
            document.querySelector(".sb-list"),
            document.querySelector(".sorting-by-last-message"),
            document.querySelector("textarea")
        ];

        sbIconDrag.addEventListener("click", () => sbBarIcons.classList.toggle("sb-hide"));

        elementsToHideIcons.forEach(el => el?.addEventListener("click", () => sbBarIcons.classList.add("sb-hide")));
    </script>
<?php } ?>

<?php

/*
 * ----------------------------------------------------------
 * LANGUAGES BOX
 * ----------------------------------------------------------
 *
 * Display the languages selector lightbox
 *
 */

function sb_languages_box()
{ ?>
    <div class="sb-lightbox sb-languages-box" data-source="">
        <div class="sb-top-bar">
            <div><?php sb_e('Choose a language') ?></div>
            <div>
                <a class="sb-close sb-btn-icon">
                    <i class="bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="sb-main sb-scroll-area"></div>
    </div>
<?php } ?>

<?php

/*
 * ----------------------------------------------------------
 * ROUTING AGENTS LIST
 * ----------------------------------------------------------
 *
 * Display the agents list for the routing
 *
 */

function sb_routing_select($exclude_id = false)
{
    $agents = sb_db_get('SELECT id, first_name, last_name FROM sb_users WHERE (user_type = "agent" OR user_type = "admin")' . ($exclude_id ? (' AND id <> ' . sb_db_escape($exclude_id)) : ''), false);
    $code = '<div class="sb-inline sb-inline-agents"><i class="bi-person-raised-hand" style="padding: 0px 5px;"></i><h3>' . sb_('Agent') . '</h3><div id="conversation-agent" class="sb-select"><p>' . sb_('None') . '</p><ul class="sb-responsive-absolute-position"><li data-id="" data-value="">' . sb_('None') . '</li>';
    for ($i = 0; $i < count($agents); $i++) {
        $code .= '<li data-id="' . $agents[$i]['id'] . '">' . $agents[$i]['first_name'] . ' ' . $agents[$i]['last_name'] . '</li>';
    }
    echo $code . '</ul></div></div>';
}

?>

<?php

/*
 * ----------------------------------------------------------
 * INSTALLATION BOX
 * ----------------------------------------------------------
 *
 * Display the form to install Routin Cloud
 *
 */

function sb_installation_box($error = false)
{
    global $SB_LANGUAGE;
    $SB_LANGUAGE = isset($_GET['lang']) ? $_GET['lang'] : strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));

?>

    <!-- *
 * ----------------------------------------------------------
 * SETUP DATABASE 
 * ----------------------------------------------------------
 *
 * -->

    <div class="sb-main sb-admin sb-admin-start">
        <form class="sb-intall sb-admin-box">
            <?php if ($error === false || $error == 'installation') echo '<div class="sb-info"></div>';
            else die('<div class="sb-info sb-active">' . sb_('We\'re having trouble connecting to your database. Please edit the file config.php and check your database connection details. Error: ') . $error . '.</div>'); ?>
            <div class="sb-top-bar">
                <div class="sb-title"><?php sb_e('Setup') ?></div>
                <div class="sb-text">
                    <?php sb_e('Please complete the process by entering the database details below. If you are not sure about these details, please contact us for support.') ?>
                </div>
            </div>

            <div class="sb-main">
                <div id="db-name" class="sb-input">
                    <span>Database Name</span>
                    <input type="text" required placeholder="Enter name" />
                </div>
                <div id="db-user" class="sb-input">
                    <span>Username</span>
                    <input type="text" placeholder="Enter username" />
                </div>
                <div id="db-password" class="sb-input">
                    <span>Password</span>
                    <input type="text" placeholder="Enter password" />
                </div>
                <div id="db-host" class="sb-input">
                    <span>Host URL</span>
                    <input type="text" placeholder="Enter host or leave empty" required />
                </div>
                <div id="db-port" class="sb-input">
                    <span>Port</span>
                    <input type="text" placeholder="Enter port" />
                </div>
                <?php if ($error === false || $error == 'installation') { ?>
                    <div class="sb-text">
                        <div class="sb-title">Create your account</div>
                    </div>
                    <div id="first-name" class="sb-input">
                        <span>First Name</span>
                        <input type="text" required placeholder="Enter first name" />
                    </div>
                    <div id="last-name" class="sb-input">
                        <span>Last Name</span>
                        <input type="text" required placeholder="Enter last name" />
                    </div>
                    <div id="email" class="sb-input">
                        <span>Email</span>
                        <input type="email" required placeholder="Enter email" />
                    </div>
                    <div id="password" class="sb-input">
                        <span>Password</span>
                        <input type="password" required placeholder="Enter password" />
                    </div>
                    <div id="password-check" class="sb-input">
                        <span>Repeat Password</span>
                        <input type="text" required placeholder="Repeat password" />
                    </div>
                <?php } ?>
                <div class="sb-bottom">
                    <div class="sb-btn sb-submit-installation">Create account</div>
                </div>
            </div>

            <style>
                ::-webkit-scrollbar {
                    width: 10px !important;
                }

                ::-webkit-scrollbar-track {
                    background-color: #f1f1f1 !important;
                }

                ::-webkit-scrollbar-thumb {
                    background-color: #888 !important;
                    border-radius: var(--chat-rounded-size-7) !important;
                }

                ::-webkit-scrollbar-thumb:hover {
                    background-color: #555 !important;
                }

                ::-webkit-scrollbar-thumb:active {
                    background-color: #333 !important;
                }
            </style>

        </form>
    </div>

<?php } ?>
<?php

/*
 * ----------------------------------------------------------
 * ADMIN AREA
 * ----------------------------------------------------------
 *
 * Display the administration area
 *
 */

function sb_component_admin()
{
    $sb_settings = json_decode(file_get_contents(SB_PATH . '/resources/json/settings.json'), true);
    $active_user = sb_get_active_user(false, true);
    $collapse = sb_get_setting('collapse') ? ' sb-collapse' : '';
    $apps = [
        ['SB_WHATSAPP', 'whatsapp', '<i class="bi bi-wind"></i> Cloud API <small style="color:var(--color-green);">(1)</small>', 'Lets your users reach you via WhatsApp. Read and reply to all messages sent to your WhatsApp Business account directly from Routin Cloud.'],
        ['SB_WHATSMEOW', 'whatsmeow', '<i class="bi bi-whatsapp"></i> WhatsApp <small style="color:var(--color-green);">(2)</small>', 'Lets your users reach you via WhatsApp. Read and reply to all messages sent to your WhatsApp Business account directly from Routin Cloud.'],
        ['SB_WAWEB', 'waweb', '<i class="bi bi-whatsapp"></i> WhatsApp <small style="color:var(--color-green);">(3)</small>', 'Lets your users reach you via WhatsApp. Read and reply to all messages sent to your WhatsApp Business account directly from Routin Cloud.'],
        ['SB_TELEGRAM', 'telegram', '<i class="bi-telegram"></i> Telegram Bot', 'Connect your Telegram bot to Routin Cloud to read and reply to all messages sent to your Telegram bot directly in Routin Cloud.'],
        ['SB_GBM', 'gbm', '<i class="bi-google"></i> Google', 'Read and reply to messages sent from Google Search, Maps and brand-owned channels directly in Routin Cloud.'],
        ['SB_TWITTER', 'twitter', '<i class="bi-twitter-x"></i> X', 'Lets your users reach you via Twitter. Read and reply to messages sent to your Twitter account directly from Routin Cloud.'],
        ['SB_MESSENGER', 'messenger', '<i class="bi-messenger"></i> Messenger', 'Read, manage and reply to all messages sent to your Facebook pages and Instagram accounts directly from Routin Cloud.'],
        ['SB_TICKETS', 'tickets', 'Tickets', 'Provide help desk support to your customers by including a ticket area, with all chat features included, on any web page in seconds.'],
    ];
    $logged = $active_user && sb_is_agent($active_user);
    $supervisor = sb_supervisor() ? sb_get_setting('supervisor') : false;
    $is_admin = $active_user && sb_is_agent($active_user, true, true) && !$supervisor;
    $sms = sb_get_multi_setting('sms', 'sms-user');
    $css_class = ($logged ? 'sb-admin' : 'sb-admin-start') . (sb_get_setting('rtl-admin') || (defined('SB_CLOUD_DEFAULT_RTL')) ? ' sb-rtl' : '') . ($supervisor ? ' sb-supervisor' : '');
    $active_areas = [
        'users' => $is_admin || ($supervisor && $supervisor['supervisor-users-area']),
        'settings' => $is_admin || ($supervisor && $supervisor['supervisor-settings-area']),
        'reports' => ($is_admin && !sb_get_multi_setting('performance', 'performance-reports')) || ($supervisor && $supervisor['supervisor-reports-area'])
    ]; // temp delete sb_get_setting('admin-agents-users-area')
    if ($supervisor && !$supervisor['supervisor-send-message']) {
        echo '<style>.sb-board .sb-conversation .sb-editor,#sb-start-conversation,.sb-top-bar [data-value="sms"],.sb-top-bar [data-value="email"],.sb-menu-users [data-value="message"],.sb-menu-users [data-value="sms"],.sb-menu-users [data-value="email"] { display: none !important; }</style>';
    }
?>
    <div class="sb-main <?php echo $css_class ?>" style="opacity: 0">
        <?php if ($logged) { ?>
            <div class="sb-header">
                <div class="sb-admin-nav">

                    <?php
                    $admin_icon = sb_get_setting('admin-icon', STMBX_URL . '/media/icons.svg');
                    if ($admin_icon == STMBX_URL . '/media/icons.svg') {
                        // Si el valor devuelto es igual al valor predeterminado, imprime el SVG directamente
                        echo '
        <svg class="rotimg" version="1.1" style="width:30px;" id="Layer_1" xmlns="http://www.w3.org/2000/svg" x="0" y="0" viewBox="0 0 387.3 339.6" xml:space="preserve"><style>.st0{fill:var(--chat-text-primary);}</style><path class="st0" d="M192.8 318.2h85.5v16.6l64.2-36.5-64.2-36.5v16.6h-85.9c-30.7 0-59.5-11.9-81.3-33.5-21.9-21.7-34-50.6-34.1-81.5-.1-26.4 8.7-51.5 24.9-72H54.6c-11.5 22-17.5 46.7-17.4 71.5 0 85.7 69.8 155.3 155.6 155.3m0-310.5H59.1l26.1 19.9-26.1 19.9h134.1c63.4 0 115.1 51.5 115.3 115 .1 26.7-9.3 53-26.2 73.6h47.8c12-22.4 18.4-47.6 18.3-73.1.1-85.7-69.8-155.3-155.6-155.3m-149.1 45"/><path class="st0" d="M231.2 180.4c6-3.4 11-8.1 14.8-14.2 4.9-7.7 7.4-16.6 7.4-26.4v-.2c0-9.9-2.2-18.6-6.5-25.7-4.3-7.3-10.6-12.9-18.7-16.8-7.8-3.8-17.3-5.7-28.1-5.7h-60.6V236h38.7v-48.4h17.5l24.9 48.4H261zm-53-56.3h18.5c5.5 0 9.7 1.4 12.8 4.2 3 2.7 4.4 6.5 4.4 11.4v.2c0 5.1-1.4 8.8-4.2 11.5-3 2.8-7.1 4.1-12.7 4.1h-18.9v-31.4z"/></svg>';
                    } else {
                        // Si se proporciona un valor diferente al predeterminado, muestra la imagen
                        echo '<img class="rotimg" style="position: fixed;bottom: 30px;height:30px;" src="' . $admin_icon . '" />';
                    }
                    ?>
                    <div>
                        <a id="sb-conversations" class="sb-active bi-chat-left-dots">
                            <span class="routin-left-tooltip">
                                <?php sb_e('Conversations') ?>
                            </span>
                        </a>
                        <?php
                        if ($active_areas['users']) echo '<a id="sb-users" class="bi-people" ><span class="routin-left-tooltip">' . sb_('Users') . '</span></a>';
                        if ($active_areas['reports']) echo '<a id="sb-reports" class="bi-chart"><span class="routin-left-tooltip">' . sb_('Reports') . '</span></a>';
                        if ($active_areas['settings']) echo '<a id="sb-settings" class="bi-gear-fill"><span class="routin-left-tooltip">' . sb_('Settings') . '</span></a>';

                        ?>
                    </div>

                </div>

                <div style="color:var(--chat-text-primary);" class="sb-admin-nav-right sb-menu-mobile">
                    <i class="bi-three-dots-vertical bottom"></i>
                    <div class="sb-desktop">
                        <div class="sb-account">
                            <img src="<?php echo STMBX_URL ?>/media/user.svg" />
                            <div style="box-shadow:none;height: 143px;background: #3b73ff00;top: -132px;width: 50px;">
                                <ul class="sb-menu" style="min-width:142px">
                                    <li data-value="status" style="padding-left: 30px;padding-top:12px" class="sb-online"> <?php sb_e('Online') ?></li>
                                    <li href="#" class="themeToggleBtn"><i class="bi-circle" style=" background: var(--chat-app-theme-color); border-radius: 24px; color: white; padding:0.8px 1px 0px 1px"></i> <?php sb_e('Tema') ?></li>
                                    <hr>
                                    <li data-value="logout"><i class="bi-power"></i>
                                        <?php sb_e('Logout') ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php echo '<div style="cursor: pointer;" class="help-center"><i style="color:var(--chat-text-primary);" class="bi bi-app-indicator"></i></div>' ?>
                        <?php include 'notifications.php'; ?>


                    </div>
                    <div class="sb-mobile" style="top: -150px;animation:scale-up-br 0.2s cubic-bezier(0,1.45,1,1);-webkit-animation:scale-up-br 0.2s cubic-bezier(0,1.45,1,1);padding:8px;font-size: 1.1rem;font-weight: 500;">
                        <a href="#" class="sb-online" data-value="status"><?php sb_e('Online') ?></a>
                        <a href="#" class="themeToggleBtn"> <i class="bi-circle" style=" background: var(--chat-app-theme-color); border-radius: 24px; color: white; padding:0.8px 1px 0px 1px"></i> <?php sb_e('Tema') ?></a>
                        <!-- <a href="#" class="startConversation"> <i class="bi-wind"></i> <?php sb_e('WhatsApp') ?></a> START CONVERSATION CREATION -->
                        <hr>
                        <a href="#" class="logout"><i class="bi bi-power"></i> <?php sb_e('Logout') ?></a>

                    </div>

                </div>
            </div>
            <main>
                <div class="sb-active sb-area-conversations">

                    <div class="sb-board">
                        <div class="sb-admin-list">
                            <div class="sb-top">
                                <div class="sb-select inbox">
                                    <p class="non-hover" data-value="0">
                                        <i class="bi-inboxes-fill"></i>&nbsp; <?php sb_e('Inbox') ?><span> </span>
                                    </p>
                                    <ul style="min-width: 8rem;max-height: none;">
                                        <li data-value="0" class="sb-active">
                                            <i class="bi bi-arrow-clockwise"></i>&nbsp; <?php sb_e('Inbox') ?>
                                            <span></span>
                                        </li>
                                        <hr>
                                        <li data-value="6">
                                            <i class="bi-stars"></i>&nbsp; <?php sb_e('My chats') ?>
                                            <span></span>
                                        </li>
                                        <li data-value="3">
                                            <i class="bi-archive"></i>&nbsp; <?php sb_e('Archived') ?>
                                        </li>
                                        <?php if ($is_admin) { ?>
                                            <li data-value="4">
                                                <i class="bi-box"></i>&nbsp; <?php sb_e('Container') ?>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                                <div class="sb-flex">
                                    <?php sb_conversations_filter() ?>
                                    <div class="sb-search-btn">
                                        <i class="sb-icon bi-search"></i>
                                        <input type="text" autocomplete="false" name="search" placeholder="<?php sb_e('Search for keywords or users...') ?>" />
                                    </div>
                                </div>
                            </div>

                            <div class="sb-scroll-area pt-50">
                                <ul class="sorting-by-last-message"></ul>
                            </div>
                        </div>
                        <div class="sb-conversation">
                            <div class="sb-top">
                                <i class="sb-btn-back bi-chevron-left"></i>
                                <div class="sb-labels"></div>
                                <a class="routin-calls-chat"></a>

                                <a class="routin-top-tip"></a>

                                <div class="sb-menu-mobile sb-menu-top extra-background-color">
                                    <i class="bi-three-dots-vertical top bkg-color-menu"></i>
                                    <ul class="ul-nav-top-mobile">
                                        <li class="li-data-content">
                                            <a class="a-details open-profile">
                                                <i class="i-details bi-info-circle"></i>
                                                <span>
                                                    <?php sb_e('Details') ?>
                                                </span>
                                            </a>
                                        </li>
                                        <?php
                                        if ($is_admin || sb_get_setting('agents-delete') || sb_get_multi_setting('agents', 'agents-delete-conversation') || ($supervisor && $supervisor['supervisor-delete-conversation'])) {
                                            echo '<li class="li-data-content">
        <a class="a-details" data-value="delete">
        <i class="i-details bi-robot"></i>
            <span>
                ' . sb_('Delete conversation') . '
            </span>
        </a>
    </li>';
                                        }
                                        ?>
                                        <li class="li-data-content">
                                            <a class="a-details" data-value="archive">
                                                <i class="i-details bi-archive"></i>
                                                <span>
                                                    <?php sb_e('Archive conversation') ?>
                                                </span>
                                            </a>
                                        </li>
                                        <li class="li-data-content">
                                            <a class="a-details" data-value="read">
                                                <i class="i-details bi-check-all"></i>
                                                <span>
                                                    <?php sb_e('Mark as unread') ?>
                                                </span>
                                            </a>
                                        </li>
                                        <li class="li-data-content">
                                            <a class="a-details" data-value="inbox">
                                                <i class="i-details bi-arrow-up-left-circle-fill"></i>
                                                <span>
                                                    <?php sb_e('Send to inbox') ?>
                                                </span>
                                            </a>
                                        </li>
                                        <?php
                                        if ($is_admin || sb_get_setting('agents-delete') || sb_get_multi_setting('agents', 'agents-delete-conversation') || ($supervisor && $supervisor['supervisor-delete-conversation'])) {
                                            echo '<li class="li-data-content">
        <a class="a-details" data-value="empty-trash">
        <i class="i-details bi-trash"></i>
            <span>
                ' . sb_('Empty trash') . '
            </span>
        </a>
    </li>';
                                        }
                                        ?>
                                    </ul>


                                </div>
                                <div class="sb-label-date-top"></div>

                            </div>
                            <div class="sb-list"></div>
                            <div class=" api-cloud-notif" id="floatingText">
                                <p><i class="bi-info-circle-fill"></i> Esta conversación dura 24 horas. Pasado el tiempo <i class="bi bi-plus-square-dotted"></i> <i class="bi bi-wind"></i> envía una plantilla HSM <a style="color: var(--blue-root-color)" href="https://developers.facebook.com/docs/whatsapp/pricing" target="_blank"> ¿Por qué pasa esto? </a>.</p>
                            </div>

                            <?php sb_component_editor(true); ?>
                            <div class="sb-no-conversation-message">
                                <div style=" text-align: start; margin: 0px 60px;">
                                    <h3>
                                        <?php sb_e('Select a conversation or start a new one') ?>
                                    </h3>
                                    <p>
                                        <?php sb_e('Select a conversation from the left menu or start a new conversation from the users area.') ?>
                                    </p>
                                </div>
                            </div>
                            <?php
                            if (sb_get_setting('chat-sound-admin') != 'n' || sb_get_setting('online-users-notification')) {
                                echo '<audio id="sb-audio" preload="auto"><source src="' . STMBX_URL . '/media/sound.mp3" type="audio/mpeg"></audio><audio id="sb-audio-out" preload="auto"><source src="' . STMBX_URL . '/media/sound-out.mp3" type="audio/mpeg"></audio>';
                            }
                            ?>
                        </div>


                        <!--added-->
                        <div class="sb-user-details sb-top">
                            <div class="sb-top">

                            </div>

                            <div class="sb-scroll-area">
                                <div class="close-button-div"><i class="bi-x-lg no-show sb-btn-collapse collapse"></i></div>
                                <!-- <div class="open-profile sb-profile sb-profile-detail">
                                    <i style="font-size: var(--chat-text-size-1-3);" class="bi-pencil-square"></i>
                                    <span class="sb-name span-profile-detail"></span>
                                </div> -->
                                <div class="sb-panel-details no-overflow">
                                    <h3>Enrutamiento manual</h3>
                                    <p class="description-p">Asigna conversaciones con agentes y equipos de forma manual sin el chatbot.</p>
                                    <?php sb_departments('custom-select'); ?>
                                    <?php sb_routing_select() ?>
                                </div>
                                <?php

                                echo '<div class="sb-panel-details sb-panel-tags">';
                                echo '<i class="bi-plus-lg"></i><h3>' . sb_('Tags') . '</h3>';
                                echo '<p class="description-p">Crea etiquetas para filtrar búsqueda de chats.</p><div id="tags-container" class="tagged">';
                                echo '<span class="sb-active">';
                                echo '<i class="bi-tags"></i></span>';
                                echo '</div></div>';
                                if (!sb_get_setting('disable-notes')) {
                                    echo '<div class="sb-panel-details sb-panel-notes' . $collapse . '"><i class="bi-pencil"></i><h3>' . sb_('Notes') . '</h3><p class="description-p">Anota información relevante sobre la conversación.</p><div></div></div>';
                                }
                                if (!sb_get_setting('disable-attachments')) {
                                    echo '<div class="sb-panel-details sb-panel-attachments' . $collapse . '"></div>';
                                }

                                if (sb_get_setting('routing') || (sb_get_multi_setting('agent-hide-conversations', 'agent-hide-conversations-active') && sb_get_multi_setting('agent-hide-conversations', 'agent-hide-conversations-menu'))) {
                                    // sb_routing_select();

                                }
                                ?>


                                <h3 class="h3-c">
                                    <?php sb_e('User conversations') ?>
                                </h3>
                                <p class="description-c">Conversaciones generadas por agente.</p>
                                <ul class="sb-user-conversations"></ul>
                            </div>
                            <div class="sb-no-conversation-message"></div>
                        </div>
                    </div>
                    <i class="sb-btn-collapse sb-left bi-chevron-left"></i>
                    <i class="sb-btn-collapse sb-right bi-chevron-right"></i>
                </div>
                <?php if ($active_areas['users']) { ?>
                    <div class="sb-area-users">
                        <div class="sb-top-bar">
                            <div>
                                <h2 class="sb-hide">
                                    <?php sb_e('Users list') ?>
                                </h2>
                                <div class="sb-menu-wide sb-menu-users">
                                    <div class="sb-nav sb-nav-only">
                                        <?php sb_e('Users list') ?>
                                        <span data-count="0"></span>
                                    </div>

                                    <!--added-->
                                    <ul class="dropdown-content">


                                        <li class="start-group-button" data-type="lead">
                                            <?php sb_e('Leads') ?>
                                            <span data-count="0">(0)</span>
                                        </li>
                                        <li class="middle-group-button-right" data-type="visitor">
                                            <?php sb_e('Live chat') ?>
                                            <span data-count="0">(0)</span>
                                        </li>
                                        <li class="middle-group-button-left sb-hide" data-type="all">
                                            <?php sb_e('Total') ?>
                                            <span data-count="0">(0)</span>
                                        </li>

                                        <?php if ($is_admin || sb_get_setting('admin-agents-tab') || sb_get_multi_setting('agents', 'agents-tab')) {
                                            echo '<li  class="end-group-button"  data-type="agent">' . sb_('Team') . '</li>';
                                        } // temp delete sb_get_setting('admin-agents-tab')
                                        ?>


                                        <!-- <li class="sb-hide" data-type="all">
                                            <?php sb_e('Total') ?>
                                            <span data-count="0">(0)</span>
                                        </li>

                                        <li class="sb-hide" data-type="user">
                                            <?php sb_e('Users') ?>
                                            <span data-count="0">(0)</span>
                                        </li>
                                        <li class="sb-hide" data-type="online">
                                            <?php sb_e('Online') ?>
                                        </li> -->

                                    </ul>

                                </div>


                            </div>
                            <div>

                                <div class="sb-menu-mobile">
                                    <i class="bi-three-dots-vertical"></i>
                                    <ul id="hideOnSearchClick">
                                        <?php if ($supervisor || $is_admin) { ?>
                                            <li class="flex-add-users">
                                                <a class="routin-users-top-tip sb-new-user">
                                                    <i class="bi-person-circle"></i>
                                                    <span class="routin-users-top-content"><?= sb_('Add user') ?></span>
                                                </a>
                                            </li>
                                        <?php } ?>
                                        <li class="flex-buttons-users transition-opacity">
                                            <div style="display: none;">
                                                <input type="file" id="csvimport" name="csv" class="form-control" required>
                                            </div>
                                            <a data-value="csvimport" id="csv_contacts" class="routin-buttons-top-tip not-show-small-screen">
                                                <i class="bi-filetype-csv"></i>
                                                <span class="routin-buttons-top-content"><?= sb_('Upload CSV') ?></span>
                                            </a>
                                        </li>
                                        <li class="flex-buttons-users transition-opacity">
                                            <a data-value="email" class="routin-buttons-top-tip data-sb-tooltip=" <?php sb_e('Send email') ?>">
                                                <i class="bi-envelope-at"></i>
                                                <span class="routin-buttons-top-content"><?php sb_e('Send email') ?></span>
                                            </a>
                                        </li>
                                        <!-- <li class="flex-buttons-users transition-opacity">
                                            <a data-value="message" class="routin-buttons-top-tip">
                                                <i class="bi-megaphone"></i>
                                                <span class="routin-buttons-top-content"><?php sb_e('Push messages') ?></span>

                                            </a>
                                        </li> -->
                                        <?php if (sb_is_agent() && $is_admin) { ?>
                                            <li class="flex-buttons-users transition-opacity">
                                                <a data-value="csv" class="routin-buttons-top-tip bi-google">
                                                    <span class="routin-buttons-top-content"><?= sb_('Download CSV') ?></span>

                                                </a>
                                            </li>
                                        <?php } ?>
                                        <!-- <li class="flex-buttons-users transition-opacity">
                                            <a style="display: none;" class="routin-buttons-top-tip">
                                                <i class="bi-chat-text"></i>
                                                <spanclass="routin-buttons-top-content">Does nothing</span>

                                            </a>
                                        </li> -->
                                        <li class="flex-buttons-users transition-opacity">
                                            <a data-value="delete" class="routin-buttons-top-tip" style="display: none;">
                                                <i class="bi-trash"></i>
                                                <span class="routin-buttons-top-content"><?php sb_e('Delete users') ?></span>
                                            </a>
                                        </li>
                                        <?php if ($sms) { ?>
                                            <li class="flex-buttons-users transition-opacity">
                                                <a data-value="sms" class="routin-buttons-top-tip">
                                                    <i class="bi-chat-square-dots"></i>
                                                    <span class="routin-buttons-top-content"><?= sb_('Send text message') ?></span>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>



                                <div class="sb-search-btn">
                                    <i class="sb-icon bi-search"></i>
                                    <input type="text" autocomplete="false" name="search" placeholder="<?php sb_e('Search') ?>" />
                                </div>

                            </div>
                        </div>

                        <div class="sb-scroll-area">
                            <table class="sb-table sb-table-users">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" />
                                        </th>
                                        <th data-field="first_name">
                                            <?php sb_e('Full name') ?>
                                        </th>
                                        <?php sb_users_table_extra_fields() ?>
                                        <th data-field="email">
                                            <?php sb_e('Email') ?>
                                        </th>
                                        <th data-field="user_type">
                                            <?php sb_e('Type') ?>
                                        </th>
                                        <th data-field="last_activity">
                                            <?php sb_e('Last activity') ?>
                                        </th>
                                        <th data-field="creation_time" class="sb-active">
                                            <?php sb_e('Registration date') ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="sb-loading sb-loading-table"></div>
                    </div>
                <?php } ?>

                <?php if ($active_areas['settings']) { ?>
                    <div class="sb-area-settings">
                        <div class="sb-top-bar">
                            <div>
                                <h2>
                                    <?php sb_e('Settings') ?>
                                </h2>
                            </div>
                            <div>
                                <a class="sb-btn sb-save-changes sb-icon">
                                    <i class="bi-check-lg"></i><?php sb_e('Save changes') ?>
                                </a>

                            </div>
                        </div>
                        <div class="sb-tab">
                            <div class="sb-nav">
                                <div><?php sb_e('Settings') ?></div>
                                <ul style="padding-left: 0;">
                                    <li id="tab-admin" class="sb-active">
                                        <i class="bi-person-fill-gear"></i> <?php sb_e('Admin') ?>
                                    </li>

                                    <li id="tab-automatica">
                                        <i class="bi-robot"></i> <?php sb_e('Automática') ?>
                                    </li>
                                    <li id="tab-saved-replies">
                                        <i class="bi-envelope-open"></i> <?php sb_e('Respuestas Rápidas') ?>
                                    </li>
                                    <li id="tab-notifications">
                                        <i class="bi-app-indicator"></i> <?php sb_e('Notifications') ?>
                                    </li>
                                    <li id="tab-mailbox">
                                        <i class="bi-mailbox"></i> <?php sb_e('Mailbox') ?>
                                    </li>
                                    <li id="tab-various">
                                        <i class="bi-box-seam"></i> <?php sb_e('Miscellaneous') ?>
                                    </li>

                                    <hr>
                                    <?php for ($i = 0; $i < count($apps); $i++) {
                                        if (defined($apps[$i][0])) echo '<li id="tab-' . $apps[$i][1] . '">' . sb_($apps[$i][2]) . '</li>';
                                    } ?>
                                    <li class="sb-hide" id="tab-apps">
                                        <?php sb_e('Integraciones') ?>
                                    </li>
                                    <hr>

                                    <li id="tab-chat">
                                        <i class="bi-chat-text"></i> <?php sb_e('Chat') ?>
                                    </li>
                                    <li id="tab-form">
                                        <i class="bi-person-vcard"></i> <?php sb_e('Form') ?>
                                    </li>
                                    <li id="tab-design">
                                        <i class="bi-paint-bucket"></i> <?php sb_e('Design') ?>
                                    </li>

                                </ul>
                            </div>
                            <div class="sb-content sb-scroll-area">
                                <div class="sb-active">
                                    <?php sb_populate_settings('admin', $sb_settings) ?>
                                </div>

                                <div>
                                    <?php sb_populate_settings('automatica', $sb_settings) ?>
                                </div>
                                <div>
                                    <?php sb_populate_settings('saved-replies', $sb_settings) ?>
                                </div>
                                <div>
                                    <?php sb_populate_settings('notifications', $sb_settings) ?>
                                </div>
                                <div>
                                    <?php sb_populate_settings('mailbox', $sb_settings) ?>
                                </div>
                                <div>
                                    <?php sb_populate_settings('miscellaneous', $sb_settings) ?>
                                </div>
                                <?php sb_apps_area($apps) ?>
                                <div>
                                    <?php sb_populate_settings('chat', $sb_settings) ?>
                                </div>
                                <div>
                                    <?php sb_populate_settings('form', $sb_settings) ?>
                                </div>
                                <div>
                                    <?php sb_populate_settings('design', $sb_settings) ?>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($active_areas['reports']) { ?>
                    <div class="sb-area-reports sb-loading">
                        <div class="sb-top-bar">
                            <div>
                                <h2><?php sb_e('Reports') ?></h2>
                            </div>
                            <div>
                                <div class="sb-setting sb-type-text"><input id="sb-date-picker" name="search" placeholder="00/00/0000 - 00/00/0000" type="text" /></div>
                            </div>
                        </div>
                        <div class="sb-tab">
                            <div class="sb-nav sb-nav-only">
                                <div><?php sb_e('Reports') ?></div>
                                <ul>
                                    <p class="sb-tab-nav-title">
                                        <?php sb_e('General') ?> <a style="vertical-align: middle; cursor:pointer;"></a>
                                    </p>
                                    <li id="leads">
                                        <?php sb_e('Leads') ?>
                                    </li>
                                    <li id="conversations" class="sb-active">
                                        <?php sb_e('All conversations') ?>
                                    </li>
                                    <li id="missed-conversations">
                                        <?php sb_e('Missed') ?>
                                    </li>
                                    <li id="conversations-time">
                                        <?php sb_e('Total time') ?>
                                    </li>
                                    <li id="status-client">
                                        
                                        <?php sb_e('Tagged') ?>
                                    </li>

                                    <p class="sb-tab-nav-title">
                                        <?php sb_e('Agents') ?> <a style="vertical-align: middle; cursor:pointer;"></a>
                                    </p>
                                    <li id="agents-conversations">
                                        <?php sb_e('Agent conversations') ?>
                                    </li>
                                    <li id="agents-conversations-time">
                                        <?php sb_e('Agent conversations time') ?>
                                    </li>
                                    <li id="agents-response-time">
                                        <?php sb_e('Agent response time') ?>
                                    </li>
                                    
                                    <li id="agents-ratings">
                                        <?php sb_e('Agent ratings') ?>
                                    </li>

                                    <p class="sb-tab-nav-title">
                                        <?php sb_e('Direct messages') ?> <a style="vertical-align: middle; cursor:pointer;"></a>
                                    </p>
                                    <li id="direct-messages">
                                        <?php sb_e('Chat messages') ?>
                                    </li>
                                    <li id="direct-emails">
                                        <?php sb_e('Emails') ?>
                                    </li>
                                    <li id="direct-sms">
                                        <?php sb_e('Text messages') ?>
                                    </li>
                                    <p class="sb-tab-nav-title">
                                        <?php sb_e('Live chat') ?> <a style="vertical-align: middle; cursor:pointer;"></a>
                                    </p>
                                    <li id="visitors">
                                        <?php sb_e('Visitors') ?>
                                    </li>
                                    <!-- <li id="users">
                                        <?php sb_e('Users') ?>
                                    </li> -->

                                    <li id="countries">
                                        <?php sb_e('Countries') ?>
                                    </li>
                                    <!-- <li id="languages">
                                        <?php sb_e('Languages') ?>
                                    </li> -->
                                    <li id="browsers">
                                        <?php sb_e('Browsers') ?>
                                    </li>
                                    <li id="os">
                                        <?php sb_e('Operating systems') ?>
                                    </li>


                                </ul>
                            </div>
                            <div class="sb-content sb-scroll-area">
                                <div class="sb-reports-tags" id="status-client">
                                    <p class="sb-reports-text"></p>
                                    <div class="sb-tags">
                                    </div>
                                </div>


                                <div class="sb-reports-chart">
                                    <div class="chart-cnt"><canvas></canvas></div>
                                </div>
                                <div class="sb-reports-sidebar">
                                    <div class="sb-title sb-reports-title"></div>
                                    <p class="sb-reports-text"></p>
                                    <div class="sb-collapse">
                                        <div>
                                            <table class="sb-table"></table>
                                        </div>
                                    </div>
                                </div>


                                <p class="sb-no-results"><?php echo sb_('No data found.') ?></p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </main>
            <?php

            sb_profile_box();
            sb_profile_edit_box();
            sb_dialog();
            sb_updates_box();

            if (!sb_get_setting('disable-notes')) sb_notes_box();
            if (!sb_get_setting('disable-tags')) sb_tags_box();

            sb_send_template_box();
            sb_direct_message_box();
            if ($is_admin || $supervisor) {
                //  if (agent) {

                sb_requirements_box();
            }

            ?>
            <form class="sb-upload-form-admin sb-upload-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                <input type="file" name="files[]" class="sb-upload-files" multiple />
            </form>
            <div class="sb-info-card"></div>
        <?php } else {
            sb_login_box();
        } ?>
        <div class="sb-lightbox sb-lightbox-media">
            <div></div>
            <i class="bi-x-lg"></i>
        </div>
        <div class="sb-lightbox-overlay"></div>
        <div class="sb-loading-global sb-loading sb-lightbox"></div>
        <input type="email" name="email" style="display:none" autocomplete="email" />
        <input type="text" name="hidden" style="display:none" autocomplete="new-password" />
    </div>


<?php } ?>
<?php

/*
 * ----------------------------------------------------------
 * HTML FUNCTIONS
 * ----------------------------------------------------------
 *
 * 1. Echo the apps settings and apps area
 * 2. Echo the apps conversation panel container
 * 3. Code check
 * 4. Return the users table extra fields
 * 5. Return the conversations filter
 * 
 */

function sb_apps_area($apps)
{
    $apps_php = [];
    $wp = defined('SB_WP');
    $code = '';
    for ($i = 0; $i < count($apps); $i++) {
        if (defined($apps[$i][0])) {
            $code .= '<div>' . sb_populate_app_settings($apps[$i][1]) . '</div>';
        }
    }
    $code .= '<div><div class="sb-apps">';
    for ($i = 1; $i < count($apps); $i++) {
        if (($wp && !in_array($apps[$i][0], $apps_php)) || (!$wp && !in_array($apps[$i][0], ['SB_WOOCOMMERCE', 'SB_UMP', 'SB_ARMEMBER']))) {
            $code .= '<div data-app="' . $apps[$i][1] . '">' . (defined($apps[$i][0]) ? '<i class="bi-check-lg"></i>' : '') . ' <img src="' . STMBX_URL . '/media/apps/' . $apps[$i][1] . '.svg" /><h2>' . $apps[$i][2] . '</h2><p>' . sb_s($apps[$i][3]) . '</p></div>';
        }
    }
    echo $code . '</div></div>';
}


function sb_users_table_extra_fields()
{
    $extra_fields = sb_get_setting('user-table-extra-columns');
    $count = $extra_fields && !is_string($extra_fields) ? count($extra_fields) : false;
    if ($count) {
        $code = '';
        for ($i = 0; $i < $count; $i++) {
            $slug = $extra_fields[$i]['user-table-extra-slug'];
            $code .= '<th data-field="' . $slug . '" data-extra="true">' . sb_string_slug($slug, 'string') . '</th>';
        }
        echo $code;
    }
}
function sb_conversations_filter()
{
    // Verificar si los filtros están deshabilitados
    if (!sb_get_setting('disable-filters')) return;

    // Obtener los departamentos y contar cuántos hay
    $departments = sb_get_setting('departments');
    $count = is_array($departments) ? count($departments) : 0;

    // Generar el botón de filtro y la lista de departamentos
    $code = '<div class="sb-filter-btn"><i class="bi-filter"></i><div><div class="sb-select' . ($count ? '' : ' sb-hide') . '">';
    $code .= '<p><i class="bi-building"></i> &nbsp;' . sb_('All departments') . '</p><ul style="min-width: 8rem;max-height: none;">';
    $code .= '<li data-value=""><i class="bi-arrow-clockwise"></i> &nbsp;' . sb_('All departments') . '</li><hr>';

    // Añadir cada departamento a la lista
    for ($i = 0; $i < $count; $i++) {
        $code .= '<li data-value="' . $departments[$i]['department-id'] . '">';
        $code .= '<i class="bi bi-inbox-fill"></i> &nbsp; ' . ucfirst(sb_($departments[$i]['department-name'])) . '</li>';
    }
    $code .= '</ul></div>';

    // Definir los canales de comunicación
    $sources = [
        ['wa', 'WhatsApp <small style="color:var(--color-green);">(1)</small>', 'SB_WHATSAPP', 'bi-wind'], // WhatsApp
        ['ww', 'WhatsApp <small style="color:var(--color-green);">(2)</small>', 'SB_WHATSMEOW', 'bi-whatsapp'], // WhatsApp QR
        ['wx', 'WhatsApp <small style="color:var(--color-green);">(3)</small>', 'SB_WAWEB', 'bi-whatsapp'], // WhatsApp Web
        ['tk', 'Live Chat', true, 'bi-chat-dots'], // Live Chat
        ['tg', 'Telegram', 'SB_TELEGRAM', 'bi-telegram'], // Telegram
        ['fb', 'Messenger', 'SB_MESSENGER', 'bi-messenger'], // Messenger
        ['ig', 'Instagram', 'SB_MESSENGER', 'bi-instagram'], // Instagram
        ['tw', 'X', 'SB_TWITTER', 'bi-twitter-x'], // Twitter
        ['bm', 'Google', 'SB_GBM', 'bi-google'], // Google
        ['wc', 'WeChat', false, ''], // WeChat (no icon provided)
        ['tm', 'SMS', false, ''] // SMS (no icon provided)
    ];

    // Generar la lista de canales de comunicación
    $code .= '<div class="sb-select"><p><i class="bi-collection"></i><span> &nbsp; ' . sb_('All channels') . '</span></p><ul style="min-width: 8rem;max-height: none;">';
    $code .= '<li data-value=""><i class="bi bi-arrow-clockwise"></i> &nbsp;' . sb_('All channels') . '</li><hr>';

    // Añadir cada canal a la lista
    for ($i = 0; $i < count($sources); $i++) {
        if ($sources[$i][2] === true || defined($sources[$i][2])) {
            $icon_class = $sources[$i][3]; // Obtener la clase del icono
            $icon_html = $icon_class ? '<i class="bi ' . $icon_class . '"></i>' : ''; // Generar HTML del icono si se proporciona la clase del icono
            $channel_name = '<span>' . $sources[$i][1] . '</span>'; // Envolver el nombre del canal en etiquetas <span>
            $style = $sources[$i][1] === 'WhatsApp' ? 'style="visibility: visible;"' : ''; // Agregar estilo en línea para anular la propiedad de visibilidad para el canal de WhatsApp
            $code .= '<li data-value="' . $sources[$i][0] . '"' . $style . '>' . $icon_html . ' &nbsp;' . $channel_name . '</li>';
        }
    }

    $code .= '</ul></div>';
    $code .= '</div></div>';

    echo $code;
}
?>