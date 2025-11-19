// Lightweight chat client abstraction using existing REST endpoints.
// Provides EventEmitter-style interface and adaptive polling without requiring WebSockets.
(function(global){
  function ChatClient(opts){
    this.conversationId = opts.conversationId;
    this.fetchUrl = opts.fetchUrl || 'messages_api.php?action=messages&conversation_id=' + encodeURIComponent(this.conversationId);
    this.intervalMin = opts.intervalMin || 8000; // 8s after activity
    this.intervalMax = opts.intervalMax || 45000; // backoff upper bound
    this.interval = this.intervalMin;
    this.unseenIds = new Set();
    this.lastMessageId = null;
    this.handlers = { message: [], batch: [], error: [] };
    this.active = false;
    this.timer = null;
    this.visibilityBound = false;
  }

  ChatClient.prototype.on = function(evt, fn){
    if (this.handlers[evt]) this.handlers[evt].push(fn);
    return this;
  };

  ChatClient.prototype.emit = function(evt, payload){
    (this.handlers[evt]||[]).forEach(fn => { try { fn(payload); } catch(e){ console.error('ChatClient handler error', e); } });
  };

  ChatClient.prototype._schedule = function(){
    if (!this.active) return;
    const delay = document.hidden ? Math.max(this.interval, 60000) : this.interval;
    this.timer = setTimeout(() => this._poll(), delay);
  };

  ChatClient.prototype._poll = function(){
    fetch(this.fetchUrl, { cache: 'no-store' })
      .then(r => r.json())
      .then(data => {
        if (!data.messages) return;
        const fresh = [];
        for (let i=0;i<data.messages.length;i++){
          const m = data.messages[i];
            // messages_api returns all recent messages; filter duplicates client-side
            if (!this.unseenIds.has(m.id)) {
              this.unseenIds.add(m.id);
              fresh.push(m);
            }
        }
        if (fresh.length) {
          // Reset backoff when new messages arrive
          this.interval = this.intervalMin;
          this.emit('batch', fresh);
          fresh.forEach(m => this.emit('message', m));
          this.lastMessageId = fresh[fresh.length-1].id;
        } else {
          // Increase backoff up to max
          this.interval = Math.min(this.interval + 5000, this.intervalMax);
        }
      })
      .catch(err => {
        this.emit('error', err);
        // On error, increase backoff but keep trying
        this.interval = Math.min(this.interval + 10000, this.intervalMax);
      })
      .finally(() => this._schedule());
  };

  ChatClient.prototype.start = function(){
    if (this.active) return;
    this.active = true;
    if (!this.visibilityBound) {
      document.addEventListener('visibilitychange', () => {
        if (!this.active) return;
        if (document.hidden) {
          // Pause current timer; resume when visible
          if (this.timer) clearTimeout(this.timer);
        } else {
          // When visible again, poll soon
          if (this.timer) clearTimeout(this.timer);
          this.interval = this.intervalMin;
          this._poll();
        }
      });
      this.visibilityBound = true;
    }
    this._poll();
  };

  ChatClient.prototype.stop = function(){
    this.active = false;
    if (this.timer) clearTimeout(this.timer);
  };

  global.ChatClient = ChatClient;
})(window);
