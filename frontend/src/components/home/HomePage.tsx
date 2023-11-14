/// <reference path='../common/Commits.d.ts' />
/// <reference path='../../interfaces/State.d.ts' />

import * as React from 'react';
import { useEffect } from 'react';
import { inject, observer } from 'mobx-react';
import { useParams } from 'react-router-dom';

import CommitPanel from '../commit-panel/CommitPanel';
import CommitsTable from '../commits-table/CommitsTable';
import Navigation from '../navigation/Navigation';
import ProgressBar from '../progress-bar/ProgressBar';
import ServicePanel from '../service-panel/ServicePanel';
import UpdateNotice from './update-notice/UpdateNotice';
import VpTitle from './vp-title/VpTitle';
import WelcomePanel from '../welcome-panel/WelcomePanel';

import { fetchCommits, fetchWelcomePanel, fetchSearchConfig, hideWelcomePanel } from '../../actions';
import { AppStore } from '../../stores/appStore';
import { LoadingStore } from '../../stores/loadingStore';

import './HomePage.less';

interface HomePageProps {
  appStore?: AppStore;
  loadingStore?: LoadingStore;
}

let HomePage: React.FunctionComponent<HomePageProps> = (props: HomePageProps) => {
  const params = useParams();

  useEffect(() => {
    const { appStore } = props;
    if (params.page) {
      appStore!.setPage(params.page);
    }

    fetchWelcomePanel();
    fetchCommits();
    fetchSearchConfig();
  }, [params.page]);

  useEffect(() => {
    const page = params.page || 0;

    fetchCommits(page);
  }, [params.page]);

  const { appStore, loadingStore } = props;
  const {
    displayWelcomePanel,
    displayUpdateNotice,
    isDirtyWorkingDirectory,
  } = appStore!;
  const { progress } = loadingStore!;

  return (
    <div>
      <ProgressBar progress={progress} />
      <ServicePanel>
        <VpTitle />
      </ServicePanel>
      {isDirtyWorkingDirectory &&
        <CommitPanel />
      }
      {displayWelcomePanel &&
        <WelcomePanel onHide={hideWelcomePanel} />
      }
      {displayUpdateNotice &&
        <UpdateNotice onClick={fetchCommits} />
      }
      <Navigation />
      <CommitsTable />
    </div>
  );
}

export default inject('appStore', 'loadingStore')(observer(HomePage));
