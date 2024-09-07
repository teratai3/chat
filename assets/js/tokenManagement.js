// トークンを sessionStorage に保存する関数
const setTokens = (accessToken, refreshToken) => {
    sessionStorage.setItem('accessToken', accessToken);
    sessionStorage.setItem('refreshToken', refreshToken);
};

// アクセストークンを取得する関数
const getAccessToken = () => sessionStorage.getItem('accessToken');

// リフレッシュトークンを取得する関数
const getRefreshToken = () => sessionStorage.getItem('refreshToken');

// トークンを sessionStorage から削除する関数
const clearTokens = () => {
    sessionStorage.removeItem('accessToken');
    sessionStorage.removeItem('refreshToken');
};

// アクセストークンを取得し、sessionStorageに保存する関数
const fetchAccessToken = async () => {
    let response;
    try {
        response = await fetch('/chat/api/access_token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        // レスポンスをJSONとしてパース
        const data = await response.json();
        // レスポンスがOKか確認
        if (!response.ok) {
            throw new Error(data?.error ? data.error : 'トークンのリフレッシュに失敗しました。');
        }
        // 成功した場合にトークンを返す
        return { success: true, accessToken: data.access_token, refreshToken: data.refresh_token, status: response.status };
    } catch (error) {
        console.error('アクセストークンの取得中にエラーが発生しました:', error);
        // エラー発生時の処理
        return { success: false, message: error, status: response.status };
    }
}


const refreshAccessToken = async (refreshToken) => {
    let response;
    try {
        if (!refreshToken) {
            throw new Error("リフレッシュトークンが見つかりません。");
        }

        // リフレッシュトークンを使って新しいアクセストークンを取得する
        response = await fetch('/chat/api/refresh_token', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + refreshToken,
                'Content-Type': 'application/json'
            },
        });

        const data = await response.json();


        if (!response.ok) {
            throw new Error(data?.error ? data.error : 'トークンのリフレッシュに失敗しました。');
        }

        const newAccessToken = data.access_token;

        return { success: true, token: newAccessToken, status: response.status };
    } catch (error) {
        console.error('トークンのリフレッシュ中にエラーが発生しました:', error);
        // 必要に応じてエラーハンドリングやリダイレクトを追加
        return { success: false, message: error, status: response.status };
    }
}

// JWTをデコードしてペイロードを取得する関数
const decodeToken = (token) => {
    try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        return payload;
    } catch (error) {
        console.error('トークンのデコード中にエラーが発生しました:', error);
        return null;
    }
};

// トークンの有効期限が切れているかを確認する関数
const isTokenExpired = (token) => {
    const payload = decodeToken(token);
    if (!payload || !payload.exp) {
        return true; // トークンが無効または有効期限がない場合は期限切れとみなす
    }
    const now = Math.floor(Date.now() / 1000); // 現在の時間を秒単位で取得
    return payload.exp < now; // expが現在の時間よりも過去であれば期限切れ
};

export { setTokens, getAccessToken, getRefreshToken, clearTokens, refreshAccessToken, isTokenExpired,fetchAccessToken };