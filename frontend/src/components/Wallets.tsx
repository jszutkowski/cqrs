import * as React from "react";
import {useEffect, useState} from "react";
import {
    Container,
    createStyles,
    Fab,
    Grid,
    makeStyles,
    Paper,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Theme
} from "@material-ui/core";
import api from "../api/api";
import {io} from "socket.io-client";
import {Events} from "../sockets/Events";
import ISimpleWallets from "../view/ISimpleWallets";
import AddIcon from '@material-ui/icons/Add';
import {Link} from 'react-router-dom';
import IWalletCreated from "../sockets/events/IWalletCreated";
import IPointsAdded from "../sockets/events/IPointsAdded";

const useStyles = makeStyles((theme: Theme) =>
    createStyles({
        root: {
            position: 'relative',
            height: '100%'
        },
        fab: {
            position: 'absolute',
            bottom: theme.spacing(2),
            right: theme.spacing(2),
        },
    }),
);

export default function Wallets() {

    const classes = useStyles();

    const [walletsInitialized, setWalletsInitialized] = useState(false);
    const [walletsFetched, setWalletsFetched] = useState(false);
    const [socketInstance, setSocketInstance] = useState<any | null>(null);
    const [wallets, setWallets] = useState<ISimpleWallets>({});

    useEffect(() => {
        return () => {
            if (null !== socketInstance) {
                socketInstance.disconnect();
            }
        }
    }, [socketInstance]);

    const addWallet = () => api.createWallet();

    const fetchWallets = () => {
        if (true === walletsInitialized) {
            return;
        }

        setWalletsInitialized(true);

        api
            .getWallets()
            .then(response => {
                setWallets(response.data);
                setWalletsFetched(true);
            })
    }

    const initializeSockets = () => {
        if (false === walletsFetched || null !== socketInstance) {
            return;
        }

        const socket = io(process.env.SOCKETS_URL);

        setSocketInstance(socket);

        socket.on(Events.CONNECTED, () => {
            console.log('Connected to sockets');
        });

        socket.emit(Events.SUBSCRIBE_WALLETS);

        socket.on(Events.WALLET_CREATED, (event: IWalletCreated) => {
            console.log('On wallet created');
            onWalletCreated(event);
        });

        socket.on(Events.POINTS_ADDED, (event: IPointsAdded) => {
            console.log('On points added');
            onAddedPoints(event);
        });
    }

    const onWalletCreated = (event: IWalletCreated) => {
        let walletId = event.walletId;

        setWallets((prevWallets: ISimpleWallets) => {
            return {
                ...prevWallets,
                [walletId]: event.balance
            };
        })
    }

    const onAddedPoints = (event: IPointsAdded) => {
        setWallets((prevWallets: ISimpleWallets) => {
            return {
                ...prevWallets,
                [event.walletId]: (prevWallets[event.walletId] || 0) + event.amount
            };
        })
    }

    initializeSockets();
    fetchWallets();

    return (
        <Container maxWidth="sm">
            <Grid container>
                <Grid item xs={12}>
                    <TableContainer component={Paper}>
                        <Table aria-label="simple table">
                            <TableHead>
                                <TableRow>
                                    <TableCell>Identifier</TableCell>
                                    <TableCell>Balance</TableCell>
                                    <TableCell align="center">Actions</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {Object.keys(wallets).map((walletId: string) => (
                                    <TableRow key={walletId}>
                                        <TableCell>{walletId}</TableCell>
                                        <TableCell>{wallets[walletId]}</TableCell>
                                        <TableCell align="center">
                                            <Link to={`/wallet/${walletId}`}>Show</Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </TableContainer>
                    <Fab aria-label="Add" className={classes.fab} color="secondary" onClick={addWallet}>
                        <AddIcon/>
                    </Fab>
                </Grid>
            </Grid>
        </Container>
    );
}
