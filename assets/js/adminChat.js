import { clearTokens, refreshAccessToken, isTokenExpired } from './tokenManagement';

function chatApp() {
    return {
        websocket: null,
        messageInput: '',
        messages: [],
        init() {
            // // トークンを取得 (サーバーサイドから埋め込んだものを利用)
            this.checkAndRefreshToken();
            // 10分ごとにトークンをリフレッシュ
            setInterval(() => {
                this.checkAndRefreshToken();
            }, 10 * 60 * 1000); // 10分（ミリ秒単位）
        },
        async checkAndRefreshToken() {
            let accessToken = window.chatTokens.accessToken;
            let refreshToken = window.chatTokens.refreshToken;

            //console.log(isTokenExpired(accessToken));
            // アクセストークンがない、または期限が切れている場合にリフレッシュ
            if (isTokenExpired(accessToken)) {
                const result = await refreshAccessToken(refreshToken);
                if (!result.success) {
                    alert(result.message);
                    return;
                } else {
                    accessToken = result.token;
                }
            }

            // WebSocket接続がまだ確立されていなければ接続を初期化
            if (!this.websocket) {
                this.initWebSocketConnection(accessToken);
            }
        },

        async initWebSocketConnection(accessToken) {
            const websocketUrl = `ws://localhost:8080?token=${encodeURIComponent(accessToken)}`;
            this.websocket = new WebSocket(websocketUrl);

            this.websocket.onopen = () => {
                let msg = {
                    command: "subscribe"
                };
                this.websocket.send(JSON.stringify(msg));
                console.log("WebSocket接続が確立されました。");
            };

            this.websocket.onmessage = (e) => {
                const response = JSON.parse(e.data);

                this.messages.push({
                    message: response.message,
                    created_at: new Date(response.created_at).toLocaleTimeString("ja-JP", {
                        timeZone: "Asia/Tokyo",
                        hour: "2-digit",
                        minute: "2-digit"
                    }),
                    user_name: response.user_name,
                    user_flag: response.user_flag,
                    is_bot: response.is_bot
                });

                this.$nextTick(() => {
                    document.querySelector(".chat-room").scrollTop = document.getElementById('message-box').scrollHeight;
                });
            };

            this.websocket.onerror = (e) => {
                console.error("WebSocketエラーが発生しました：", e);
            };

            this.websocket.onclose = (e) => {
                console.log("WebSocket接続が閉じられました。");
            };
        },

        sendMessage() {
            if (this.messageInput === "") {
                alert("メッセージを入力してください。");
                return;
            }

            if(this.messageInput.length > 1000){
                alert("メッセージは1000文字以内で入力してください。");
                return;
            }
            
            let msg = {
                message: this.messageInput,
                command: "message"
            };

            this.websocket.send(JSON.stringify(msg));
            this.messageInput = '';
        },
    }
}

// chatApp関数をグローバルスコープに公開
window.chatApp = chatApp;