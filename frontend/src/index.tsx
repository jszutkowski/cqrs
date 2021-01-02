import React from 'react';
import ReactDOM from 'react-dom';
import './index.scss';
import Wallet from "./components/Wallet";
import Wallets from "./components/Wallets";
import { Router, Route, Switch } from 'react-router-dom';
import Login from "./components/Login";
import { history } from "./history/history";

ReactDOM.render(
    <Router history={history}>
        <Switch>
            <Route exact path="/">
                <Wallets />
            </Route>
            <Route path="/wallet/:walletId">
                <Wallet />
            </Route>
            <Route exact path="/login">
                <Login />
            </Route>
        </Switch>
    </Router>
    , document.getElementById('root')
);
