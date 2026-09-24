/*!
 * Tsoka Vendor API SDK — v1 (2026-06-30)
 * Lightweight, dependency-free client for the Tsoka vendor API.
 *
 * Browser:  <script src="https://portal.tsokatravel.com/sdk/tsoka.js"></script>
 *           const tsoka = Tsoka({ key: 'pk_live_...' });
 * ESM:      import Tsoka from 'https://portal.tsokatravel.com/sdk/tsoka.js'
 *
 * Use a PUBLISHABLE key (pk_…) in the browser. Keep SECRET keys (sk_…) server-side.
 */
(function (root, factory) {
  if (typeof define === 'function' && define.amd) define([], factory);
  else if (typeof module === 'object' && module.exports) module.exports = factory();
  else root.Tsoka = factory();
}(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var DEFAULT_BASE = 'https://portal.tsokatravel.com/api/v';
  var VERSION = '2026-06-30';

  function TsokaError(status, payload) {
    var err = new Error((payload && payload.error && payload.error.message) || ('HTTP ' + status));
    err.name = 'TsokaError';
    err.status = status;
    err.code = (payload && payload.error && payload.error.code) || 'error';
    err.payload = payload;
    return err;
  }

  function createClient(config) {
    config = config || {};
    if (!config.key) throw new Error('Tsoka: an API key is required, e.g. Tsoka({ key: "pk_live_..." })');

    var baseUrl = (config.baseUrl || DEFAULT_BASE).replace(/\/+$/, '');
    var version = config.version || VERSION;

    function buildQuery(query) {
      if (!query) return '';
      var parts = Object.keys(query)
        .filter(function (k) { return query[k] !== undefined && query[k] !== null && query[k] !== ''; })
        .map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(query[k]); });
      return parts.length ? ('?' + parts.join('&')) : '';
    }

    function request(method, path, opts) {
      opts = opts || {};
      var headers = {
        'Authorization': 'Bearer ' + config.key,
        'Accept': 'application/json',
        'Tsoka-Version': version
      };
      if (opts.body !== undefined) headers['Content-Type'] = 'application/json';
      if (opts.idempotencyKey) headers['Idempotency-Key'] = opts.idempotencyKey;

      var url = baseUrl + (path.charAt(0) === '/' ? path : '/' + path) + buildQuery(opts.query);

      return fetch(url, {
        method: method,
        headers: headers,
        body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined
      }).then(function (res) {
        if (res.status === 304) return { notModified: true };
        return res.text().then(function (text) {
          var data = text ? JSON.parse(text) : null;
          if (!res.ok) throw TsokaError(res.status, data);
          return data;
        });
      });
    }

    return {
      version: version,
      baseUrl: baseUrl,
      request: request,

      me: function () { return request('GET', '/me'); },

      services: {
        list: function (type, query) { return request('GET', '/services/' + type, { query: query }); },
        get: function (type, id) { return request('GET', '/services/' + type + '/' + id); },
        availability: function (type, id, query) { return request('GET', '/services/' + type + '/' + id + '/availability', { query: query }); }
      },

      bookings: {
        list: function (query) { return request('GET', '/bookings', { query: query }); },
        get: function (code) { return request('GET', '/bookings/' + code); },
        // create requires a SECRET key (server-side). idempotencyKey strongly recommended.
        create: function (payload, opts) { return request('POST', '/bookings', { body: payload, idempotencyKey: (opts || {}).idempotencyKey }); }
      },

      tanova: {
        generate: function (payload, opts) { return request('POST', '/tanova/generate', { body: payload, idempotencyKey: (opts || {}).idempotencyKey }); },
        trips: function (query) { return request('GET', '/tanova/trips', { query: query }); },
        trip: function (id) { return request('GET', '/tanova/trips/' + id); }
      },

      analytics: {
        summary: function () { return request('GET', '/analytics/summary'); }
      }
    };
  }

  createClient.VERSION = VERSION;
  return createClient;
}));
