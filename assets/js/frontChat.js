import { setTokens, getAccessToken, getRefreshToken, clearTokens, refreshAccessToken, isTokenExpired,fetchAccessToken } from './tokenManagement';

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
            let accessToken = getAccessToken();
            let refreshToken = getRefreshToken();

            if(!accessToken || !refreshToken){
                //アクセストークンかリフレッシュトークンがセッションストレージにない場合
                const token = await fetchAccessToken();
            
                if (!token.success) {
                    alert(token.message);
                    return;
                } else {
                    accessToken = token.accessToken;
                    refreshToken = token.refreshToken;
                }
            }

            if (!getAccessToken() || !getRefreshToken() || isTokenExpired(accessToken)) {
                setTokens(accessToken, refreshToken);
            }
            


            //console.log(isTokenExpired(accessToken));

    
            // アクセストークンがない、または期限が切れている場合にリフレッシュ
            if (!accessToken || isTokenExpired(accessToken)) {
                const result = await refreshAccessToken(getRefreshToken());
                
                if (!result.success) {
                    if(result.status == 401){
                        clearTokens();
                    }
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
            // const websocketUrl = `wss://dreamy-takeo-8487.lolipop.io:8080/?token=${encodeURIComponent(accessToken)}`;
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
                    window.scrollTo(0, document.body.scrollHeight);
                });
            };

            this.websocket.onerror = (e) => {
                clearTokens();
                console.error("WebSocketエラーが発生しました：", e);
                alert("エラーが発生しました。リロードして改善されない場合は、お手数ですが、お問い合わせください");
            };

            this.websocket.onclose = (e) => {
                console.warn("WebSocketが閉じられました。Close event:", e);
                alert("チャットの接続が閉じました。リロードしてもう一度接続してください");
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