import IPoints from "./IPoints";

export default interface IWallet {
    walletId: string,
    balance: number,
    points: IPoints[]
}
