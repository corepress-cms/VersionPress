import * as React from 'react';
import classNames from 'classnames';
import { observer } from 'mobx-react';
import { Link, useParams } from 'react-router-dom';

import config from '../../../config/config';

const routes = config.routes;

interface FooterProps {
  pages: number[];
}

const Footer: React.FunctionComponent<FooterProps> = ({
  pages,
}) => {
  const params = useParams();
  return (
    <tfoot>
      <tr>
        <td className='vp-table-pagination' colSpan={6}>
          {pages.map((page: number) => {
            return page === 1
              ? <Link
                  className={classNames({ active: !params.page || `${page}` === params.page })}
                  to={routes.home}
                  key={page}
                >{page}</Link>
              : <Link
                  className={classNames({ active: `${page}` === params.page })}
                  to={`/${routes.page}/${page}`}
                  key={page}
                >{page}</Link>;
          })}
        </td>
      </tr>
    </tfoot>
)};

export default observer(Footer);
