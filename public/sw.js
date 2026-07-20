/**
 * Copyright 2018 Google Inc. All Rights Reserved.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *     http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// If the loader is already loaded, just stop.
if (!self.define) {
  let registry = {};

  // Used for `eval` and `importScripts` where we can't get script URL by other means.
  // In both cases, it's safe to use a global var because those functions are synchronous.
  let nextDefineUri;

  const singleRequire = (uri, parentUri) => {
    uri = new URL(uri + ".js", parentUri).href;
    return registry[uri] || (
      
        new Promise(resolve => {
          if ("document" in self) {
            const script = document.createElement("script");
            script.src = uri;
            script.onload = resolve;
            document.head.appendChild(script);
          } else {
            nextDefineUri = uri;
            importScripts(uri);
            resolve();
          }
        })
      
      .then(() => {
        let promise = registry[uri];
        if (!promise) {
          throw new Error(`Module ${uri} didn’t register its module`);
        }
        return promise;
      })
    );
  };

  self.define = (depsNames, factory) => {
    const uri = nextDefineUri || ("document" in self ? document.currentScript.src : "") || location.href;
    if (registry[uri]) {
      // Module is already loading or loaded.
      return;
    }
    let exports = {};
    const require = depUri => singleRequire(depUri, uri);
    const specialDeps = {
      module: { uri },
      exports,
      require
    };
    registry[uri] = Promise.all(depsNames.map(
      depName => specialDeps[depName] || require(depName)
    )).then(deps => {
      factory(...deps);
      return exports;
    });
  };
}
define(['./workbox-00be5606'], (function (workbox) { 'use strict';

  importScripts("/push-handlers.js");
  self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
      self.skipWaiting();
    }
  });

  /**
   * The precacheAndRoute() method efficiently caches and responds to
   * requests for URLs in the manifest.
   * See https://goo.gl/S9QRab
   */
  workbox.precacheAndRoute([{
    "url": "assets/vue-number-input-I451mA7H.js",
    "revision": null
  }, {
    "url": "assets/vue-number-input-BwyOsCLx.css",
    "revision": null
  }, {
    "url": "assets/useValidationAlert-D3KqIBMY.js",
    "revision": null
  }, {
    "url": "assets/useNonce-CyiH1NK4.js",
    "revision": null
  }, {
    "url": "assets/useKbd-kRIii9yh.js",
    "revision": null
  }, {
    "url": "assets/useGeolocation-XBlr3ecq.css",
    "revision": null
  }, {
    "url": "assets/useGeolocation-BDPasw7z.js",
    "revision": null
  }, {
    "url": "assets/simpleDatePicker-QwAPYj9R.js",
    "revision": null
  }, {
    "url": "assets/seccionGen-HJrDBHo_.js",
    "revision": null
  }, {
    "url": "assets/marker-shadow-f7SaPCxT.png",
    "revision": null
  }, {
    "url": "assets/marker-shadow-DkSEwwB4.js",
    "revision": null
  }, {
    "url": "assets/marker-shadow-CLLlcC51.css",
    "revision": null
  }, {
    "url": "assets/marker-icon-hN30_KVU.png",
    "revision": null
  }, {
    "url": "assets/lodash-B0_NEswR.js",
    "revision": null
  }, {
    "url": "assets/layers-BWBAp2CZ.png",
    "revision": null
  }, {
    "url": "assets/layers-2x-Bpkbi35X.png",
    "revision": null
  }, {
    "url": "assets/format-L8iBeSnS.js",
    "revision": null
  }, {
    "url": "assets/app-DnGO36ff.js",
    "revision": null
  }, {
    "url": "assets/app-Cz2HK9Bh.css",
    "revision": null
  }, {
    "url": "assets/TomaDePedidos-DpQzADBL.js",
    "revision": null
  }, {
    "url": "assets/Textarea-BfX3T5Hb.js",
    "revision": null
  }, {
    "url": "assets/SyncQueue-sfVRKCXs.js",
    "revision": null
  }, {
    "url": "assets/SiifIconBC3-a4x8olOv.png",
    "revision": null
  }, {
    "url": "assets/SiifIconBC3-CtxvTzC-.js",
    "revision": null
  }, {
    "url": "assets/SelectValue-Cw43qIt6.js",
    "revision": null
  }, {
    "url": "assets/SeguimientoPedidos-DP4i86Pq.js",
    "revision": null
  }, {
    "url": "assets/SIIF_Info_cons01-NYywaDGK.js",
    "revision": null
  }, {
    "url": "assets/ReporteAgenda-CAn8foKm.js",
    "revision": null
  }, {
    "url": "assets/Profile-DKVKEHKi.js",
    "revision": null
  }, {
    "url": "assets/Preguntas-Bcz3SzQc.js",
    "revision": null
  }, {
    "url": "assets/Password-BREdtk4y.js",
    "revision": null
  }, {
    "url": "assets/PanelDual-YKq2VnwP.css",
    "revision": null
  }, {
    "url": "assets/PanelDual-BYWL8L3Y.js",
    "revision": null
  }, {
    "url": "assets/Operadores-RMsUQxFH.js",
    "revision": null
  }, {
    "url": "assets/NuevoReporte-CdlRKaTA.js",
    "revision": null
  }, {
    "url": "assets/Notificacion-Cm2Bf-Su.js",
    "revision": null
  }, {
    "url": "assets/Monitor-D2YRA18H.js",
    "revision": null
  }, {
    "url": "assets/Monitor-CIgf0F6L.css",
    "revision": null
  }, {
    "url": "assets/MenuGen-DHPFi2Z6.js",
    "revision": null
  }, {
    "url": "assets/Login-iNKk6YDF.js",
    "revision": null
  }, {
    "url": "assets/ListaReporte-CJDzkLFV.js",
    "revision": null
  }, {
    "url": "assets/ListaClientes-vYKy_HVx.js",
    "revision": null
  }, {
    "url": "assets/Layout-CM5JsbMc.js",
    "revision": null
  }, {
    "url": "assets/Label-9EnEKQtn.js",
    "revision": null
  }, {
    "url": "assets/InputError-BwASGHuf.js",
    "revision": null
  }, {
    "url": "assets/Input-Bd9oE5R-.js",
    "revision": null
  }, {
    "url": "assets/Gps-CGOx3MU3.js",
    "revision": null
  }, {
    "url": "assets/GlobalTable-ijwSr5mE.js",
    "revision": null
  }, {
    "url": "assets/GlobalSelect-7To52Dxa.js",
    "revision": null
  }, {
    "url": "assets/GenericCombobox-DlkKsNlL.js",
    "revision": null
  }, {
    "url": "assets/DialogTitle-DrTu6rr6.js",
    "revision": null
  }, {
    "url": "assets/Descuentos-Tn50kxJ_.js",
    "revision": null
  }, {
    "url": "assets/Dashboard-WqIEdWHt.js",
    "revision": null
  }, {
    "url": "assets/CubeIcon-B4DvvoCi.js",
    "revision": null
  }, {
    "url": "assets/Contacto-DYKIp2V6.js",
    "revision": null
  }, {
    "url": "assets/ConsultaReportes-DvTTuZXW.js",
    "revision": null
  }, {
    "url": "assets/ConsultaGerencial-k-SYLBed.css",
    "revision": null
  }, {
    "url": "assets/ConsultaGerencial-BpE3JqWR.js",
    "revision": null
  }, {
    "url": "assets/Consolidar-bQimR1vJ.js",
    "revision": null
  }, {
    "url": "assets/ComboboxViewport-CBMHE4Zw.js",
    "revision": null
  }, {
    "url": "assets/ComboboxList-BE3YCIQH.js",
    "revision": null
  }, {
    "url": "assets/ComboboxCancel-DqlHiCtY.js",
    "revision": null
  }, {
    "url": "assets/Calendario-oANGtqk4.js",
    "revision": null
  }, {
    "url": "assets/Calendario-BtBX-YBS.css",
    "revision": null
  }, {
    "url": "assets/BaseModal-CnJQnw5-.js",
    "revision": null
  }, {
    "url": "assets/Appearance-Bp8pbr65.js",
    "revision": null
  }, {
    "url": "assets/AppSidebarLayout-Ds6QiZ97.js",
    "revision": null
  }, {
    "url": "assets/AppLayout-Bz5l3GQM.js",
    "revision": null
  }], {});
  workbox.cleanupOutdatedCaches();
  workbox.registerRoute(/^https:\/\/fonts\.bunny\.net\/.*/i, new workbox.CacheFirst({
    "cacheName": "bunny-fonts-cache",
    plugins: [new workbox.ExpirationPlugin({
      maxEntries: 10,
      maxAgeSeconds: 31536000
    }), new workbox.CacheableResponsePlugin({
      statuses: [0, 200]
    })]
  }), 'GET');
  workbox.registerRoute(({
    request
  }) => request.mode === "navigate", new workbox.NetworkFirst({
    "cacheName": "pages-cache",
    "networkTimeoutSeconds": 5,
    plugins: [new workbox.ExpirationPlugin({
      maxEntries: 30,
      maxAgeSeconds: 604800
    }), new workbox.CacheableResponsePlugin({
      statuses: [0, 200]
    })]
  }), 'GET');
  workbox.registerRoute(/\/offline\/master-data/, new workbox.NetworkFirst({
    "cacheName": "master-data-cache",
    plugins: [new workbox.ExpirationPlugin({
      maxEntries: 1,
      maxAgeSeconds: 86400
    }), new workbox.CacheableResponsePlugin({
      statuses: [0, 200]
    })]
  }), 'GET');

}));
