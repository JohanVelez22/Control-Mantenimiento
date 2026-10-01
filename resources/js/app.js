// ─── GLOBAL PASSWORD VISIBILITY TOGGLE (OJITO UNIVERSAL MÓVIL Y WEB) ───────
export function initPasswordToggles(root) {
    root = root || document;
    if (!root || !root.querySelectorAll) return;
    var inputs = root.querySelectorAll('input[type="password"]');
    inputs.forEach(function (input) {
        if (input.dataset.hasPasswordToggle === 'true') return;
        input.dataset.hasPasswordToggle = 'true';

        var wrapper = input.parentElement;
        var isAlreadyWrapper = wrapper && wrapper.classList.contains('ts-password-wrapper');
        if (!isAlreadyWrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'ts-password-wrapper';
            if (input.classList.contains('w-full')) {
                wrapper.classList.add('w-full');
            }
            if (input.classList.contains('flex-1')) {
                wrapper.classList.add('flex-1');
            }

            // Transferir márgenes del input al wrapper para centrado vertical perfecto
            var marginClasses = [];
            input.classList.forEach(function (cls) {
                if (/^m[trblxy]?-\d+/.test(cls) || cls === 'my-auto' || cls === 'mx-auto') {
                    marginClasses.push(cls);
                    wrapper.classList.add(cls);
                }
            });
            marginClasses.forEach(function (cls) {
                input.classList.remove(cls);
            });

            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }

        // Asegurar atributos que evitan autocorrección intrusiva al revelar
        if (!input.hasAttribute('autocapitalize')) input.setAttribute('autocapitalize', 'none');
        if (!input.hasAttribute('autocorrect')) input.setAttribute('autocorrect', 'off');
        if (!input.hasAttribute('spellcheck')) input.setAttribute('spellcheck', 'false');

        // Balancear padding si el texto está centrado
        var isCenter = input.classList.contains('text-center') || (window.getComputedStyle && window.getComputedStyle(input).textAlign === 'center');
        if (isCenter) {
            input.classList.add('ts-password-input-center');
        } else {
            input.classList.add('ts-password-input');
        }

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ts-password-toggle';
        btn.tabIndex = -1;

        var SVG_EYE = '<svg viewBox="0 0 24 24" fill="currentColor" class="ts-eye-svg"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
        var SVG_EYE_OFF = '<svg viewBox="0 0 24 24" fill="currentColor" class="ts-eye-svg"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';

        var isInitiallyText = (input.type === 'text');
        btn.innerHTML = isInitiallyText ? SVG_EYE_OFF : SVG_EYE;
        btn.setAttribute('title', isInitiallyText ? 'Ocultar contraseña' : 'Mostrar contraseña');
        btn.setAttribute('aria-label', isInitiallyText ? 'Ocultar contraseña' : 'Mostrar contraseña');

        var lastToggleTime = 0;

        function togglePassword(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var now = Date.now();
            if (now - lastToggleTime < 350) return;
            lastToggleTime = now;

            var hadFocus = (document.activeElement === input);
            var show = (input.type === 'password');
            input.type = show ? 'text' : 'password';

            // Reemplazar completamente por el icono correspondiente (NUNCA coexisten dos)
            btn.innerHTML = show ? SVG_EYE_OFF : SVG_EYE;
            btn.setAttribute('title', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');

            // Si ya tenía el foco antes del toque/clic, mantenerlo y posicionar el cursor al final
            if (hadFocus) {
                try {
                    input.focus();
                    var len = input.value.length;
                    input.setSelectionRange(len, len);
                } catch (err) {}
            } else {
                // Des-enfocar el botón para que no se quede iluminado tras el clic
                try {
                    btn.blur();
                } catch (err) {}
            }
        }

        btn.addEventListener('click', togglePassword);
        btn.addEventListener('touchend', togglePassword);

        wrapper.appendChild(btn);

        // Al enviar formulario, asegurar type="password" para que gestores de contraseñas lo reconozcan
        var form = input.form;
        if (form && !form.dataset.hasPasswordSubmitReset) {
            form.dataset.hasPasswordSubmitReset = 'true';
            form.addEventListener('submit', function () {
                var pwInputs = form.querySelectorAll('.ts-password-wrapper input');
                pwInputs.forEach(function (pwInput) {
                    if (pwInput.type === 'text') {
                        pwInput.type = 'password';
                        var pwBtn = pwInput.parentElement.querySelector('.ts-password-toggle');
                        if (pwBtn) {
                            pwBtn.innerHTML = SVG_EYE;
                            pwBtn.setAttribute('title', 'Mostrar contraseña');
                            pwBtn.setAttribute('aria-label', 'Mostrar contraseña');
                        }
                    }
                });
            });
        }
    });
}

window.initPasswordToggles = initPasswordToggles;

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function () {
        initPasswordToggles();
        if (window.MutationObserver) {
            var observer = new MutationObserver(function (mutations) {
                var needsScan = false;
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                        needsScan = true;
                        break;
                    }
                }
                if (needsScan) {
                    initPasswordToggles();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    });
    window.addEventListener('load', function () { initPasswordToggles(); });
}
