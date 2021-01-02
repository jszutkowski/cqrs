export default interface ITokenProvider {
    getToken: () => string|null
    setToken: (token?: string) => void
    getRefreshToken: () => string|null
    setRefreshToken: (refreshToken?: string) => void
}
