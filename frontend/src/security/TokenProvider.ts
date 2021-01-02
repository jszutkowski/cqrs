import ITokenProvider from "./ITokenProvider";

const TokenProvider: ITokenProvider = {
    getToken: (): string | null => localStorage.getItem('token'),
    setToken: (token?: string): void => localStorage.setItem('token', token),

    getRefreshToken: (): string | null => localStorage.getItem('refresh_token'),
    setRefreshToken: (refreshToken?: string): void => localStorage.setItem('refresh_token', refreshToken),
}

export default TokenProvider;
